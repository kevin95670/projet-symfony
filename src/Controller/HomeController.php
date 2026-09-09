<?php

namespace App\Controller;
use App\Service\CartMerger;
use App\Repository\JeuRepository;
use App\Repository\UserRepository;
use App\Repository\CategorieRepository;
use App\Repository\WishlistItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(JeuRepository $jeuRepository,CartMerger $cartMerger, WishlistItemRepository $wishlistItemRepository, UserRepository $userRepository, CategorieRepository $categorieRepository): Response
    {

        $last_ventes = $jeuRepository->getLastJeux(10,true);
        $nb_annonces = $jeuRepository->countJeux(true);
        $nb_membres =$userRepository->countUsers();
        $nb_categories = $categorieRepository->countCategories();
        $categories = $categorieRepository->findAll();
        $jeux_petits_budget = $jeuRepository->findPetitsPrix(15,true);

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $wishlistItemsIds = [];
        if ($user) {
            $wishlistItemsIds = $wishlistItemRepository->findJeuIdsByUser($user);
        }

        $panierItemsIds = $cartMerger->getPanierItemsIds($user);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'last_ventes' => $last_ventes,
            'nb_annonces' => $nb_annonces,
            'nb_membres' => $nb_membres,
            'nb_categories' => $nb_categories,
            'categories' => $categories,
            'jeux_petits_budget' => $jeux_petits_budget,
            'wishlistItemsIds' => $wishlistItemsIds,
            'panierItemsIds' => $panierItemsIds,
        ]);
    }
}
