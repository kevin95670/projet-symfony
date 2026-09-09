<?php

namespace App\Controller;
use App\Repository\WishlistItemRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class WishlistController extends AbstractController
{
    #[Route('/wishlist', name: 'app_wishlist')]
    #[IsGranted('ROLE_USER')]
    public function index(WishlistItemRepository $wishlistItemRepository): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $wishlistItems = $wishlistItemRepository->getWishlistItemsByUserId($user->getId());
        

        return $this->render('wishlist/index.html.twig', [
            'wishlistItems' => $wishlistItems,
        ]);
    }
}
