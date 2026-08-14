<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function apercu_vendeur(UserRepository $userRepository, AvisRepository $avisRepository, string $pseudo): Response
    {

        $vendeur = $userRepository->findOneByPseudo($pseudo);

        return $this->render('user/vendeur.html.twig', [
            'controller_name' => 'UserController',
            'vendeur' => $vendeur,
            'moyenne' => $avisRepository->getMoyenneAvisByVendeur($vendeur),
        ]);
    }
}
