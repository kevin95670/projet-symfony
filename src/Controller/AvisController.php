<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\User;
use App\Enum\StatutLigne;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AvisController extends AbstractController
{
    // L'acheteur note un vendeur, une fois tous ses jeux de la commande reçus
    #[Route('/commande/{id}/avis/{vendeurId}', name: 'app_avis_creer', methods: ['POST'], requirements: ['vendeurId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function creer(Commande $commande, int $vendeurId, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sécurité : seul l'acheteur de la commande peut donner un avis
        if ($commande->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('avis-' . $commande->getId() . '-' . $vendeurId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        // Les jeux de cette commande appartenant à ce vendeur
        $lignesVendeur = [];
        foreach ($commande->getCommandeLignes() as $ligne) {
            if ($ligne->getJeu()->getVendeur()->getId() === $vendeurId) {
                $lignesVendeur[] = $ligne;
            }
        }

        if (count($lignesVendeur) === 0) {
            throw $this->createNotFoundException('Ce vendeur ne fait pas partie de cette commande.');
        }

        // On attend que tous ses jeux soient reçus
        foreach ($lignesVendeur as $ligne) {
            if ($ligne->getStatut() !== StatutLigne::RECUE) {
                $this->addFlash('error', 'Tu pourras noter ce vendeur quand tous ses jeux auront été reçus.');
                return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
            }
        }

        // Un seul avis par commande et par vendeur
        foreach ($commande->getAvis() as $avisExistant) {
            if ($avisExistant->getVendeur()->getId() === $vendeurId) {
                $this->addFlash('error', 'Tu as déjà donné ton avis sur ce vendeur.');
                return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
            }
        }

        $note = $request->request->getInt('note');
        if ($note < 1 || $note > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
        }

        $commentaire = trim($request->request->getString('commentaire'));
        if (mb_strlen($commentaire) > 255) {
            $this->addFlash('error', 'Le commentaire ne peut pas dépasser 255 caractères.');
            return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
        }

        $vendeur = $lignesVendeur[0]->getJeu()->getVendeur();

        $avis = new Avis();
        $avis->setAuteur($user);
        $avis->setVendeur($vendeur);
        $avis->setCommande($commande);
        $avis->setNote($note);
        $avis->setCommentaire($commentaire !== '' ? $commentaire : null);
        $avis->setDateAvis(new \DateTimeImmutable());

        $em->persist($avis);
        $em->flush();

        $this->addFlash('success', 'Merci pour ton avis !');

        return $this->redirectToRoute('app_commande', ['id' => $commande->getId()]);
    }
}