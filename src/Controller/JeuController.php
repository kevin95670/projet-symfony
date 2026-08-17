<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JeuController extends AbstractController
{

    #[Route('/jeu/categorie/{slug}', name: 'app_jeux_par_categorie')]
    public function apercu_jeux_par_categorie(JeuRepository $jeuRepository, string $slug): Response
    {
        $jeux = $jeuRepository->findAllByCategorie($slug);

        return $this->render('jeu/listing.html.twig', [
            'jeux' => $jeux,
            'categorie' => ['nom' => $slug],
        ]);
    }

    #[Route('/jeu/{slug}/{id}', name: 'app_jeu', requirements: ['id' => '\d+'])]
    public function apercu_jeu(JeuRepository $jeuRepository, string $slug): Response
    {
        $jeu = $jeuRepository->findOneBySlug($slug);

        return $this->render('jeu/index.html.twig', [
            'controller_name' => 'JeuController',
            'jeu' => $jeu,
        ]);
    }
}
