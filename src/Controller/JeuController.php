<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use App\Repository\CategorieRepository;
use App\Repository\WishlistItemRepository;
use App\Enum\Pegi;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Jeu;
use App\Entity\WishlistItem;
use App\Form\JeuForm;
use App\Form\JeuEditType;
use App\Repository\JeuCommentRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JeuController extends AbstractController
{

    #[Route('/jeu/categorie/{slug}', name: 'app_jeux_par_categorie')]
    public function apercu_jeux_par_categorie(JeuRepository $jeuRepository, string $slug): Response
    {
        /*$jeux = $jeuRepository->findAllByCategorie($slug);

        return $this->render('jeu/listing.html.twig', [
            'jeux' => $jeux,
            'categorie' => ['nom' => $slug],
        ]);*/

        return $this->redirectToRoute('app_jeux_liste', ['categorie' => $slug]);
    }

    #[Route('/jeux', name: 'app_jeux_liste')]
    public function jeu_recherche(JeuRepository $jeuRepository, Request $request, CategorieRepository $categorieRepository): Response
    {
        $query = $request->query->get('q', '');
        $categorySlug = $request->query->get('categorie', '');
        $prixMax = $request->query->get('prix_max', '');
        $prixMin = $request->query->get('prix_min', '');
        $pegi = $request->query->get('pegi', '');
        $criteres = [
            'query' => $query,
            'categorySlug' => $categorySlug,
            'prixMax' => $prixMax,
            'prixMin' => $prixMin,
            'pegi' => $pegi,
        ];

        $jeux = $jeuRepository->findAllByRecherche($criteres);

        return $this->render('jeu/listing.html.twig', [
            'jeux' => $jeux,
            'query' => $query,
            'categories' => $categorieRepository->findAllOrderByOrdreAlphabetique(),
            'pegis' => Pegi::cases(),
        ]);
    }

    #[Route('/jeu/{slug}/{id}', name: 'app_jeu', requirements: ['id' => '\d+'])]
    public function apercu_jeu(JeuRepository $jeuRepository,JeuCommentRepository $jeuCommentRepository, string $slug): Response
    {
        $jeu = $jeuRepository->findOneBySlug($slug);
        $comments = $jeuCommentRepository->findByJeu($jeu);

        return $this->render('jeu/index.html.twig', [
            'controller_name' => 'JeuController',
            'jeu' => $jeu,
            'comments' => $comments,
        ]);
    }

    #[Route('/jeu/{slug}/{id}/editer', name: 'app_jeu_modifier')]
    #[IsGranted('ROLE_USER')]
    public function modifierJeu(Request $request, EntityManagerInterface $em, Jeu $jeu): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifier que c'est le propriétaire du jeu
        if ($jeu->getVendeur() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce jeu.');
        }

        $form = $this->createForm(JeuEditType::class, $jeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $jeu->setMiseAJour(new \DateTimeImmutable());

            $this->addFlash('success', 'Jeu mis à jour avec succès.');
            return $this->redirectToRoute('app_ventes', ['vendeurId' => $user->getId()]);
        }

        return $this->render('jeu/modify.html.twig', [
            'form' => $form,
            'jeu' => $jeu,
        ]);
    }

    #[Route('/jeu/{id}/wishlist', name: 'app_jeu_wishlist_toggle')]
    #[IsGranted('ROLE_USER')]
    public function toggleWishlist(Jeu $jeu, Request $request, WishlistItemRepository $wishlistRepository, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $existant = $wishlistRepository->findOneByUserAndJeu($user, $jeu);

        if ($existant) {
            $wishlistRepository->deleteFromWishlist($user->getId(), $jeu->getId());
            $this->addFlash('success', 'Retiré de ta wishlist.');
        } else {
            $wishlistRepository->addToWishlist($user->getId(), $jeu->getId());
            $this->addFlash('success', 'Ajouté à ta wishlist.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_home'));
    }

    #[Route('/jeu/vendre', name: 'app_jeu_vendre')]
    #[IsGranted('ROLE_USER')]
    public function vendre_jeu(Request $request, EntityManagerInterface $em): Response
    {
        $jeu = new Jeu();
        $form = $this->createForm(JeuForm::class, $jeu);
        $slugger = new AsciiSlugger();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jeu->setVendeur($this->getUser());
            $jeu->setDateCreation(new \DateTime());
            $jeu->setSlug($slugger->slug($jeu->getTitre())->lower()->toString());
            $jeu->setStatut(\App\Enum\JeuStatut::EN_VENTE);

            $em->persist($jeu);
            $em->flush();

            $this->addFlash('success', 'Ton jeu a bien été mis en vente !');

            return $this->redirectToRoute('app_jeu', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()]);
        }

        return $this->render('jeu/vendre.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/jeu/{id}/delete', name: 'app_jeu_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete_jeu(Jeu $jeu, Request $request, EntityManagerInterface $em): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $vendeurId = $jeu->getVendeur()->getId();

        if (!$user || $user->getId() !== $vendeurId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder aux ventes d\'un autre utilisateur.');
        }

        $em->remove($jeu);
        $em->flush();

        $this->addFlash('success', 'Le jeu a bien été supprimé.');

        return $this->redirectToRoute('app_ventes', ['vendeurId' => $vendeurId]);
    }

    #[Route('/ventes/{vendeurId}', name: 'app_ventes')]
    #[IsGranted('ROLE_USER')]
    public function ventes(int $vendeurId, JeuRepository $jeuRepository): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user || $user->getId() !== $vendeurId) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder aux ventes d\'un autre utilisateur.');
        }

        $ventes = $jeuRepository->findAllVentes($vendeurId);

        return $this->render('jeu/ventes.html.twig', [
            'ventes' => $ventes,
        ]);
    }
}
