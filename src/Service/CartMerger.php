<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\PanierRepository;
use App\Repository\PanierItemRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartMerger
{
    public function __construct(
        private PanierRepository $panierRepository,
        private PanierItemRepository $panierItemRepository,
        private RequestStack $requestStack,
    ) {}

    public function mergeSessionCartIfNeeded(User $user): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            return;
        }

        $panier = $user->getPanier() ?? $this->panierRepository->findOrCreateForUser($user);

        foreach ($cart as $jeuId => $quantity) {
            $this->panierItemRepository->addToCart($panier->getId(), (int) $jeuId);
        }

        $session->remove('cart');
    }

    public function getPanierItemsIds(?User $user): array
    {
        if ($user && $user->getPanier()) {
            return array_map(
                fn($item) => $item->getJeu()->getId(),
                $user->getPanier()->getPanierItems()->toArray()
            );
        }

        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        return array_map('intval', array_keys($cart));
    }
}