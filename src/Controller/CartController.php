<?php

namespace App\Controller;

use App\Repository\PanierItemRepository;
use App\Repository\PanierRepository;
use App\Entity\Jeu;
use App\Repository\JeuRepository;
use App\Repository\PromoCodeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\CartMerger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(private CartMerger $cartMerger) {}

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request, JeuRepository $jeuRepository, PromoCodeRepository $promoRepository, PanierRepository $panierRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $items = [];
        $promoApplied = false;
        $promoCode = null;
        $discount = 0;

        // Récupère le code promo de la session
        $appliedPromoCode = $request->getSession()->get('promo_code');

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            // Le panier existe toujours, même si le compte n'en avait pas encore
            $panier = $panierRepository->findOrCreateForUser($user);

            foreach ($panier->getPanierItems() as $panierItem) {
                $items[] = [
                    'panierItem' => $panierItem,
                    'jeu' => $panierItem->getJeu(),
                    'quantity' => $panierItem->getQuantite(),
                ];
            }
            $jeux = $items;
        } else {
            $cart = $request->getSession()->get('cart', []);

            foreach ($cart as $jeuId => $quantity) {
                $jeu = $jeuRepository->find($jeuId);
                if ($jeu) {
                    $items[] = [
                        'jeu' => $jeu,
                        'quantity' => $quantity,
                    ];
                }
            }
            $jeux = $items;
        }

        // Calcule le total (les jeux vendus ne comptent pas)
        $total = 0;
        foreach ($jeux as $item) {
            if ($item['jeu']->getStatut()->name === 'EN_VENTE') {
                $total += $item['jeu']->getPrix() * $item['quantity'];
            }
        }

        // Si un code promo est appliqué
        if ($appliedPromoCode) {
            $promoCode = $promoRepository->findOneBy(['code' => $appliedPromoCode]);

            if ($promoCode && $promoCode->getDateExpiration() > new \DateTime() && $promoCode->isActif()) {
                $promoApplied = true;
                $discount = $total * ($promoCode->getPourcentage() / 100);
            } else {
                $request->getSession()->remove('promo_code');
            }
        }

        return $this->render('cart/index.html.twig', [
            'jeux' => $jeux,
            'promoApplied' => $promoApplied,
            'promoCode' => $promoCode,
            'discount' => $discount,
        ]);
    }

    #[Route('/cart/apply-promo', name: 'app_apply_promo', methods: ['POST'])]
    public function applyPromo(Request $request, PromoCodeRepository $promoRepository): Response
    {
        $promoCode = $request->request->get('promo_code');
        $request->getSession()->remove('promo_code'); // Supprime le code promo précédent avant d'appliquer le nouveau
        $promo = $promoRepository->findOneBy(['code' => $promoCode]);

        if (!$promo) {
            $this->addFlash('error', 'Code promo invalide');
        } elseif ($promo->getDateExpiration() < new \DateTime()) {
            $this->addFlash('error', 'Code promo expiré');
        } elseif (!$promo->isActif()) {
            $this->addFlash('error', 'Code promo désactivé');
        } else {
            $request->getSession()->set('promo_code', $promoCode);
            $this->addFlash('success', 'Code promo appliqué !');
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove-promo', name: 'app_remove_promo', methods: ['POST'])]
    public function removePromo(Request $request): Response
    {
        $request->getSession()->remove('promo_code');
        $this->addFlash('success', 'Code promo supprimé');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/jeu/{id}/panier', name: 'app_jeu_cart_toggle', methods: ['POST'])]
    public function toggleCart(
        Jeu $jeu,
        Request $request,
        PanierItemRepository $cartItemsRepository,
        PanierRepository $panierRepository,
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            $panier = $panierRepository->findOrCreateForUser($user);

            $existant = $cartItemsRepository->findOneByPanierAndJeu($panier, $jeu);

            if ($existant) {
                $cartItemsRepository->deleteFromCart($panier->getId(), $jeu->getId());
                $this->addFlash('success', 'Retiré du panier.');
            } else {
                $cartItemsRepository->addToCart($panier->getId(), $jeu->getId());
                $this->addFlash('success', 'Ajouté au panier.');
            }
        } else {
            $session = $request->getSession();
            $cart = $session->get('cart', []);

            if (isset($cart[$jeu->getId()])) {
                unset($cart[$jeu->getId()]);
                $this->addFlash('success', 'Retiré du panier.');
            } else {
                $cart[$jeu->getId()] = 1;
                $this->addFlash('success', 'Ajouté au panier.');
            }

            $session->set('cart', $cart);
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('/panier/supprimer/jeu/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function removeFromCart(
        Request $request,
        PanierItemRepository $cartRepository,
        PanierRepository $panierRepository,
        int $id
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            $panier = $panierRepository->findOrCreateForUser($user);
            $cartRepository->deleteFromCart($panier->getId(), $id);
            $this->addFlash('success', 'Retiré du panier.');
        } else {
            $session = $request->getSession();
            $cart = $session->get('cart', []);

            unset($cart[$id]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Retiré du panier.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    // Ancienne validation directe, sans paiement : elle est remplacée par Stripe Checkout (/panier/payer).
    // Elle ne crée plus aucune commande.
    #[Route('/panier/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(): Response
    {
        $this->addFlash('error', 'Pour valider ta commande, passe par le paiement sécurisé.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/vider', name: 'app_cart_clear')]
    public function clearCart(
        Request $request,
        PanierRepository $cartRepository,
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            $cartRepository->viderPanier($cartRepository->findOrCreateForUser($user));
            $this->addFlash('success', 'Panier vidé.');
        } else {
            $session = $request->getSession();

            $session->remove('cart');
            $this->addFlash('success', 'Panier vidé.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }
}