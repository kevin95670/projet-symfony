<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\JeuIndisponibleException;
use App\Repository\PanierRepository;
use App\Service\NotificationService;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    // Stripe appelle cette adresse quand un paiement est confirmé. Elle n'est pas protégée par la connexion.
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        PanierRepository $panierRepository,
        MailerInterface $mailer,
        NotificationService $notifications,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET');

        // Vérifie que la requête vient bien de Stripe
        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            return new Response('Signature invalide', 400);
        }

        if ($event->type !== 'checkout.session.completed') {
            // Autres événements ignorés
            return new Response('Ignoré', 200);
        }

        $session = $event->data->object;
        $panier = $panierRepository->find($session->metadata['panier_id'] ?? 0);

        // Panier introuvable ou déjà traité (Stripe peut renvoyer le même événement) : on ne fait rien
        if (!$panier || $panier->getPanierItems()->isEmpty()) {
            return new Response('Déjà traité', 200);
        }

        $livraison = $this->extraireLivraison($session->toArray());

        // Crée la commande, passe les jeux en VENDU et nettoie wishlists et paniers
        try {
            $commande = $panierRepository->checkout($panier, $livraison);
        } catch (JeuIndisponibleException $e) {
            // Un jeu a été vendu entre-temps : on rembourse le paiement, aucune commande n'est créée
            try {
                $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));
                // La clé d'idempotence évite un double remboursement si Stripe renvoie l'événement
                $stripe->refunds->create(
                    ['payment_intent' => $session->payment_intent],
                    ['idempotency_key' => 'refund-' . $session->id]
                );
            } catch (ApiErrorException $apiException) {
                // Remboursement échoué : on répond en erreur pour que Stripe renvoie l'événement
                return new Response('Remboursement impossible', 500);
            }

            $this->envoyerEmailRemboursement($mailer, $panier->getUser(), $e->jeu->getTitre());

            return new Response('Jeu indisponible, paiement remboursé', 200);
        }

        // Notification dans le site pour chaque vendeur dont un jeu vient d'être vendu
        foreach ($commande->getCommandeLignes() as $ligne) {
            $notifications->creer(
                $ligne->getJeu()->getVendeur(),
                sprintf('Ton jeu « %s » vient d\'être vendu. Pense à l\'expédier.', $ligne->getJeu()->getTitre()),
                $this->generateUrl('app_ventes_expeditions')
            );
        }

        // Email de confirmation
        try {
            $user = $panier->getUser();
            $email = (new TemplatedEmail())
                ->from(new Address('kevinleonard.info@gmail.com', 'Kevin'))
                ->to((string) $user->getEmail())
                ->subject('Merci pour votre commande !')
                ->htmlTemplate('commande/commande.html.twig')
                ->context([
                    'user' => $user,
                    'commande' => $commande,
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            // La commande est déjà enregistrée : un échec d'email ne doit pas annuler la vente
        }

        return new Response('OK', 200);
    }

    // Récupère l'adresse de livraison saisie sur la page Stripe (selon la version de l'API Stripe)
    private function extraireLivraison(array $donnees): ?array
    {
        $details = $donnees['collected_information']['shipping_details'] ?? $donnees['shipping_details'] ?? null;

        if (!$details) {
            return null;
        }

        $adresse = $details['address'] ?? [];

        return [
            'nom' => $details['name'] ?? null,
            'ligne1' => $adresse['line1'] ?? null,
            'ligne2' => $adresse['line2'] ?? null,
            'code_postal' => $adresse['postal_code'] ?? null,
            'ville' => $adresse['city'] ?? null,
            'pays' => $adresse['country'] ?? null,
        ];
    }

    private function envoyerEmailRemboursement(MailerInterface $mailer, User $user, string $titre): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('kevinleonard.info@gmail.com', 'Kevin'))
                ->to((string) $user->getEmail())
                ->subject('Ton paiement a été remboursé')
                ->htmlTemplate('commande/remboursement.html.twig')
                ->context([
                    'user' => $user,
                    'titre' => $titre,
                ]);

            $mailer->send($email);
        } catch (\Exception $e) {
            // Le remboursement est déjà fait : un échec d'email n'a pas d'impact
        }
    }
}