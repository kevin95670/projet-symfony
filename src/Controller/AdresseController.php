<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdresseController extends AbstractController
{
    // Page où l'acheteur enregistre son adresse de livraison (France uniquement)
    #[Route('/mon-compte/adresse', name: 'app_user_adresse', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $erreurs = [];
        $valeurs = $user->getAdresseLivraison() ?? [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('adresse', $request->request->getString('_token'))) {
                $this->addFlash('error', 'Session expirée, réessaie.');
                return $this->redirectToRoute('app_user_adresse');
            }

            $valeurs = [
                'nom' => trim($request->request->getString('nom')),
                'ligne1' => trim($request->request->getString('ligne1')),
                'ligne2' => trim($request->request->getString('ligne2')) ?: null,
                'code_postal' => trim($request->request->getString('code_postal')),
                'ville' => trim($request->request->getString('ville')),
                'pays' => 'FR',
            ];

            if ($valeurs['nom'] === '') {
                $erreurs[] = 'Le nom est obligatoire.';
            }
            if ($valeurs['ligne1'] === '') {
                $erreurs[] = 'L\'adresse est obligatoire.';
            }
            if (!preg_match('/^\d{5}$/', $valeurs['code_postal'])) {
                $erreurs[] = 'Le code postal doit contenir 5 chiffres.';
            }
            if ($valeurs['ville'] === '') {
                $erreurs[] = 'La ville est obligatoire.';
            }

            if (!$erreurs) {
                // Enregistrée sur le compte
                $user->setAdresseLivraison($valeurs);
                $em->flush();

                // Recopiée sur le client Stripe : la page de paiement la proposera au prochain achat
                try {
                    $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY'));

                    $adresseStripe = [
                        'name' => $valeurs['nom'],
                        'address' => [
                            'line1' => $valeurs['ligne1'],
                            'line2' => $valeurs['ligne2'] ?? '',
                            'postal_code' => $valeurs['code_postal'],
                            'city' => $valeurs['ville'],
                            'country' => 'FR',
                        ],
                    ];

                    if ($user->getStripeCustomerId()) {
                        $stripe->customers->update($user->getStripeCustomerId(), [
                            'shipping' => $adresseStripe,
                        ]);
                    } else {
                        $client = $stripe->customers->create([
                            'email' => $user->getEmail(),
                            'name' => trim($user->getPrenom() . ' ' . $user->getNom()),
                            'shipping' => $adresseStripe,
                            'metadata' => ['user_id' => $user->getId()],
                        ]);
                        $user->setStripeCustomerId($client->id);
                        $em->flush();
                    }

                    $this->addFlash('success', 'Adresse enregistrée. Elle sera proposée au prochain paiement.');
                } catch (ApiErrorException $e) {
                    // L'adresse reste enregistrée sur le compte, seule la synchro Stripe a échoué
                    $this->addFlash('error', 'Adresse enregistrée, mais la synchronisation avec Stripe a échoué. Réessaie plus tard.');
                }

                return $this->redirectToRoute('app_user_adresse');
            }
        }

        return $this->render('user/adresse.html.twig', [
            'valeurs' => $valeurs,
            'erreurs' => $erreurs,
        ]);
    }
}