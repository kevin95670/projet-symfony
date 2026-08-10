<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(JeuRepository $jeuRepository): Response
    {

        $last_ventes = $jeuRepository->getLastJeux(10,true);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'last_ventes' => $last_ventes,
        ]);
    }
}
