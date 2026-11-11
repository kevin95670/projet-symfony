<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeLignes;
use App\Enum\StatutLigne;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    // L'acheteur confirme qu'il a bien reçu le jeu
    #[Route('/commande/ligne/{id}/recue', name: 'app_commande_ligne_recue', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function ligneRecue(
        CommandeLignes $ligne,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notifications,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $commande = $ligne->getCommande();

        // Sécurité : seul l'acheteur de la commande peut confirmer la réception
        if ($commande->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('recue-' . $ligne->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        if ($ligne->getStatut() !== StatutLigne::EXPEDIEE) {
            $this->addFlash('error', 'Ce jeu n\'est pas encore expédié.');
        } else {
            $ligne->setStatut(StatutLigne::RECUE);
            $em->flush();

            // Notification dans le site pour le vendeur
            $notifications->creer(
                $ligne->getJeu()->getVendeur(),
                sprintf('L\'acheteur a bien reçu « %s ».', $ligne->getJeu()->getTitre()),
                $this->generateUrl('app_ventes_expeditions')
            );

            $this->addFlash('success', 'Merci ! La réception est bien enregistrée.');
        }

        return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
    }
}