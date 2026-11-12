<?php

namespace App\Controller;

use App\Enum\JeuStatut;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class StripeController extends AbstractController
{
    // Crée une session Stripe Checkout à partir du panier, puis redirige vers la page de paiement Stripe
    #[Route('/panier/payer', name: 'app_stripe_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function payer(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $panier = $user->getPanier();

        if (!$panier || $panier->getPanierItems()->isEmpty()) {
            $this->addFlash('error', 'Ton panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        // Un jeu vendu entre-temps ne doit pas partir au paiement : on le retire du panier
        $indisponibles = [];
        foreach ($panier->getPanierItems() as $item) {
            if ($item->getJeu()->getStatut() !== JeuStatut::EN_VENTE || $item->getJeu()->isMasque()) {
                $indisponibles[] = $item;
            }
        }

        if (count($indisponibles) > 0) {
            $titres = [];
            foreach ($indisponibles as $item) {
                $titres[] = $item->getJeu()->getTitre();
                $em->remove($item);
            }
            $em->flush();

            $this->addFlash('error', sprintf(
                'Ces jeux ne sont plus disponibles et ont été retirés de ton panier : %s.',
                implode(', ', $titres)
            ));
            return $this->redirectToRoute('app_cart');
        }

        $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));

        // Client Stripe de l'acheteur : créé au premier achat, puis réutilisé (son adresse est pré-remplie)
        if (!$user->getStripeCustomerId()) {
            $client = $stripe->customers->create([
                'email' => $user->getEmail(),
                'name' => trim($user->getPrenom() . ' ' . $user->getNom()),
                'metadata' => ['user_id' => $user->getId()],
            ]);
            $user->setStripeCustomerId($client->id);
            $em->flush();
        }

        // Une ligne par jeu du panier. Stripe attend le prix en centimes (entier)
        $lignes = [];
        foreach ($panier->getPanierItems() as $item) {
            $lignes[] = [
                'quantity' => $item->getQuantite(),
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) round($item->getJeu()->getPrix() * 100),
                    'product_data' => [
                        'name' => $item->getJeu()->getTitre(),
                    ],
                ],
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lignes,
            'customer' => $user->getStripeCustomerId(),
            // Adresse de livraison : demandée (ou pré-remplie) sur la page Stripe, France uniquement pour l'instant
            'shipping_address_collection' => [
                'allowed_countries' => ['FR'],
            ],
            // Enregistre l'adresse saisie sur le client Stripe, pour la pré-remplir au prochain achat
            'customer_update' => [
                'shipping' => 'auto',
            ],
            // Ces infos serviront au webhook pour retrouver le panier
            'metadata' => [
                'panier_id' => $panier->getId(),
                'user_id' => $user->getId(),
            ],
            'success_url' => $this->generateUrl('app_cart_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('app_cart', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // 303 : redirection vers une page externe (Stripe)
        return $this->redirect($session->url, 303);
    }

    // Page de retour après un paiement réussi
    #[Route('/panier/paiement-reussi', name: 'app_cart_success')]
    #[IsGranted('ROLE_USER')]
    public function succes(): Response
    {
        $this->addFlash('success', 'Merci ! Ton paiement est reçu, ta commande est en cours de validation.');

        return $this->redirectToRoute('app_commandes');
    }
}