<?php

namespace App\Controller;

use App\Entity\Commande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CommandeController extends AbstractController
{
    #[Route('/commande/{id}', name: 'app_commande')]
    #[IsGranted('ROLE_USER')]
    public function index(Commande $commande): Response
    {
        // Sécurité : vérifier que la commande appartient bien à l'utilisateur connecté
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commande/index.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commandes', name: 'app_commandes')]
    #[IsGranted('ROLE_USER')]
    public function list(): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        return $this->render('commande/listing.html.twig', [
            'commandes' => $user->getCommandes(),
        ]);
    }

    #[Route('/commande/{id}/confirmation', name: 'app_commande_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(Commande $commande): Response
    {
        // Sécurité : vérifier que la commande appartient bien à l'utilisateur connecté
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }
}