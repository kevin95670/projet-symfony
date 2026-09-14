<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\JeuRepository;
use App\Enum\JeuStatut;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\ProfilEditType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/vendeur/{pseudo}', name: 'vue_vendeur')]
    public function apercu_vendeur(
        UserRepository $userRepository, 
        AvisRepository $avisRepository, 
        JeuRepository $jeuRepository,
        string $pseudo
    ): Response
    {
        $vendeur = $userRepository->findOneByPseudo($pseudo);

        // Stats de ventes
        $jeux = $jeuRepository->findBy(['vendeur' => $vendeur]);
        
        // Jeux vendus
        $jeuxVendus = array_filter($jeux, fn($j) => $j->getStatut() === JeuStatut::VENDU);
        $totalJeuxVendus = count($jeuxVendus);
        
        $prixTotal = array_reduce($jeuxVendus, function($carry, $jeu) {
            return $carry + $jeu->getPrix();
        }, 0);
        
        $prixMoyen = $totalJeuxVendus > 0 ? $prixTotal / $totalJeuxVendus : 0;

        // Jeux en cours de vente
        $jeuxEnCours = array_filter($jeux, fn($j) => $j->getStatut() === JeuStatut::EN_VENTE);
        $prixTotalEnCours = array_reduce($jeuxEnCours, function($carry, $jeu) {
            return $carry + $jeu->getPrix();
        }, 0);
        $prixMoyenEnCours = count($jeuxEnCours) > 0 ? $prixTotalEnCours / count($jeuxEnCours) : 0;

        return $this->render('user/vendeur.html.twig', [
            'controller_name' => 'UserController',
            'vendeur' => $vendeur,
            'moyenne' => $avisRepository->getMoyenneAvisByVendeur($vendeur),
            'totalJeuxVendus' => $totalJeuxVendus,
            'prixMoyen' => $prixMoyen,
            'prixMoyenEnCours' => $prixMoyenEnCours,
        ]);
    }

    #[Route('/user/editer', name: 'app_user_edit')]
    #[IsGranted('ROLE_USER')]
    public function modifierUser(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfilEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('user/modify.html.twig', [
            'form' => $form,
        ]);
    }
}
