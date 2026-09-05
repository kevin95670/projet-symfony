<?php

namespace App\Controller;

use App\Repository\PanierItemRepository;
use App\Repository\PanierRepository;
use App\Entity\Jeu;
use App\Repository\JeuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\CartMerger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class CartController extends AbstractController
{
    public function __construct(private CartMerger $cartMerger) {}

    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request, JeuRepository $jeuRepository): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {

            $this->cartMerger->mergeSessionCartIfNeeded($user);

            foreach ($user->getPanier()->getPanierItems() as $panierItem) {
                $items[] = [
                    'jeu' => $panierItem->getJeu(),
                    'quantity' => $panierItem->getQuantite(),
                ];
            }
            $jeux = $items ?? [];
        } else {
            $cart = $request->getSession()->get('cart', []);

            //dd($cart); // <-- ajoute ça ici

            foreach ($cart as $jeuId => $quantity) {
                $jeu = $jeuRepository->find($jeuId);
                if ($jeu) {
                    $items[] = [
                        'jeu' => $jeu,
                        'quantity' => $quantity,
                    ];
                }
            }
            $jeux = $items ?? [];
        }

        return $this->render('cart/index.html.twig', [
            'jeux' => $jeux,
        ]);
    }

    #[Route('/jeu/{id}/panier', name: 'app_jeu_cart_toggle', methods: ['POST'])]
    public function toggleCart(
        Jeu $jeu,
        Request $request,
        PanierItemRepository $cartRepository,
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            $existant = $cartRepository->findOneByPanierAndJeu($user->getPanier(), $jeu);

            if ($existant) {
                $cartRepository->deleteFromCart($user->getPanier()->getId(), $jeu->getId());
                $this->addFlash('success', 'Retiré du panier.');
            } else {
                $cartRepository->addToCart($user->getPanier()->getId(), $jeu->getId());
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

        //dd($request->getSession()->get('cart'));
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('/panier/supprimer/jeu/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function removeFromCart(
        Request $request,
        PanierItemRepository $cartRepository,
        int $id
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $this->cartMerger->mergeSessionCartIfNeeded($user);

            $cartRepository->deleteFromCart($user->getPanier()->getId(), $id);
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

    #[Route('/panier/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        PanierRepository $cartRepository,
        MailerInterface $mailer
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour passer la commande.');
            return $this->redirectToRoute('app_login', ['redirect' => $this->generateUrl('app_cart')]);
        }

        $commande = $cartRepository->checkout($user->getPanier());
        $email = (new TemplatedEmail())
        ->from(new Address('kevinleonard.info@gmail.com', 'Kevin'))
        ->to((string) $user->getEmail())
        ->subject('Merci pour votre commande !')
        ->htmlTemplate('commande/commande.html.twig')
        ->context([
            'user' => $user,
            'commande' => $commande
        ])
        ;

        $mailer->send($email);


        return $this->redirectToRoute('app_commande_confirmation', ['id' => $commande->getId()]);
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

            $cartRepository->viderPanier($user->getPanier());
            $this->addFlash('success', 'Panier vidé.');
        } else {
            $session = $request->getSession();
            $cart = $session->get('cart', []);

            $session->remove('cart');
            $this->addFlash('success', 'Panier vidé.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }
}
