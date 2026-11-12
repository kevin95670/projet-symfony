<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use App\Repository\CategorieRepository;
use App\Repository\WishlistItemRepository;
use App\Enum\Etat;
use App\Enum\JeuStatut;
use App\Enum\Pegi;
use App\Enum\Plateforme;
use App\Service\AlerteService;
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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Attribute\Route;

final class JeuController extends AbstractController
{

    #[Route('/jeu/categorie/{slug}', name: 'app_jeux_par_categorie')]
    public function apercu_jeux_par_categorie(JeuRepository $jeuRepository, string $slug): Response
    {
        return $this->redirectToRoute('app_jeux_liste', ['categorie' => $slug]);
    }

    #[Route('/jeux', name: 'app_jeux_liste')]
    public function jeu_recherche(JeuRepository $jeuRepository, Request $request, CategorieRepository $categorieRepository): Response
    {
        // Nombre de jeux affichés par page, et page demandée dans l'URL (?page=2)
        $parPage = 12;
        $numeroPage = max(1, $request->query->getInt('page', 1));

        // La navbar envoie "q", le formulaire de filtres envoie "query" : on accepte les deux
        $query = $request->query->get('q') ?? $request->query->get('query', '');

        $filtres = [
            'query'      => $query,
            'categorie'  => $request->query->get('categorie', ''),
            'prix_min'   => $request->query->get('prix_min', ''),
            'prix_max'   => $request->query->get('prix_max', ''),
            'pegi'       => $request->query->get('pegi', ''),
            'plateforme' => $request->query->get('plateforme', ''),
            'etat'       => $request->query->get('etat', ''),
            'tri'        => $request->query->get('tri', ''),
        ];

        // Jeux de la page demandée, et nombre total de pages
        $jeux = $jeuRepository->trouverFiltres($filtres, $numeroPage, $parPage);
        $nombrePages = (int) ceil(count($jeux) / $parPage);

        return $this->render('jeu/listing.html.twig', [
            'jeux' => $jeux,
            'query' => $query,
            'categories' => $categorieRepository->findAllOrderByOrdreAlphabetique(),
            'pegis' => Pegi::cases(),
            'plateformes' => Plateforme::cases(),
            'etats' => Etat::cases(),
            'page' => $numeroPage,
            'pages' => $nombrePages,
        ]);
    }

    #[Route('/jeu/{slug}/{id}', name: 'app_jeu', requirements: ['id' => '\d+'])]
    public function apercu_jeu(Request $request, JeuRepository $jeuRepository, JeuCommentRepository $jeuCommentRepository, EntityManagerInterface $em, string $slug): Response
    {
        $jeu = $jeuRepository->findOneBySlug($slug);

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $estVendeur = $user !== null && $user === $jeu->getVendeur();

        // Annonce masquée : seuls le vendeur et l'admin peuvent la voir
        if ($jeu->isMasque() && !$estVendeur && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Cette annonce n\'est plus disponible.');
        }

        $dejaSignale = $user !== null && $em->getRepository(\App\Entity\SignalementJeu::class)->findOneBy(['jeu' => $jeu, 'auteur' => $user]) !== null;

        // États des boutons : le jeu est-il déjà dans le panier ou la liste de souhaits de l'utilisateur ?
        $dansPanier = false;
        $dansWishlist = false;

        if ($user !== null) {
            $panier = $user->getPanier();
            if ($panier !== null) {
                foreach ($panier->getPanierItems() as $item) {
                    if ($item->getJeu()?->getId() === $jeu->getId()) {
                        $dansPanier = true;
                        break;
                    }
                }
            }
            $dansWishlist = $em->getRepository(WishlistItem::class)->findOneBy(['user' => $user, 'jeu' => $jeu]) !== null;
        } else {
            // Visiteur non connecté : le panier est dans la session
            $dansPanier = isset($request->getSession()->get('cart', [])[$jeu->getId()]);
        }

        $fil = [];
        $filsVendeur = [];
        $acheteurActif = null;

        if ($user !== null && !$estVendeur) {
            // Acheteur : il voit seulement son propre fil avec le vendeur
            $fil = $jeuCommentRepository->messagesDuFil($jeu, $user);
        } elseif ($estVendeur) {
            // Vendeur : liste des acheteurs qui ont écrit, et le fil de celui qui est choisi (?fil=ID)
            $filsVendeur = $jeuCommentRepository->acheteursAvecMessages($jeu);
            $idChoisi = $request->query->getInt('fil');

            foreach ($filsVendeur as $acheteur) {
                if ($acheteur->getId() === $idChoisi) {
                    $acheteurActif = $acheteur;
                    break;
                }
            }

            if ($acheteurActif !== null) {
                $fil = $jeuCommentRepository->messagesDuFil($jeu, $acheteurActif);
            }
        }

        return $this->render('jeu/index.html.twig', [
            'controller_name' => 'JeuController',
            'jeu' => $jeu,
            'masque' => $jeu->isMasque(),
            'dejaSignale' => $dejaSignale,
            'estVendeur' => $estVendeur,
            'dansPanier' => $dansPanier,
            'dansWishlist' => $dansWishlist,
            'fil' => $fil,
            'estVendeur' => $estVendeur,
            'filsVendeur' => $filsVendeur,
            'acheteurActif' => $acheteurActif,
        ]);
    }

    #[Route('/jeu/{slug}/{id}/editer', name: 'app_jeu_modifier')]
    #[IsGranted('ROLE_USER')]
    public function modifierJeu(Request $request, EntityManagerInterface $em, Jeu $jeu, AlerteService $alertes): Response
    {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifier que c'est le propriétaire du jeu
        if ($jeu->getVendeur() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce jeu.');
        }

        // Un jeu vendu ne peut plus être modifié
        if ($jeu->getStatut() === JeuStatut::VENDU) {
            $this->addFlash('error', 'Un jeu vendu ne peut plus être modifié.');
            return $this->redirectToRoute('app_ventes', ['vendeurId' => $user->getId()]);
        }

        // Ancien prix mémorisé avant que le formulaire ne modifie l'objet
        $ancienPrix = $jeu->getPrix();

        $form = $this->createForm(JeuEditType::class, $jeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Nouvelle photo : on la remplace seulement si l'utilisateur en a envoyé une
            $this->enregistrerPhoto($form, $jeu);

            // Si le prix a changé, on garde l'ancien pour l'afficher barré sur l'annonce
            if ((float) $ancienPrix !== (float) $jeu->getPrix()) {
                $jeu->setPrixPrecedent($ancienPrix);
            }

            // Date de mise à jour définie avant l'enregistrement, pour qu'elle soit bien sauvegardée
            $jeu->setMiseAJour(new \DateTimeImmutable());
            $em->flush();

            // Alerte de baisse ou de hausse de prix, seulement si le prix a changé
            $alertes->prixModifie($jeu, $ancienPrix);

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
    public function vendre_jeu(Request $request, EntityManagerInterface $em, AlerteService $alertes): Response
    {
        $jeu = new Jeu();
        $form = $this->createForm(JeuForm::class, $jeu);
        $slugger = new AsciiSlugger();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jeu->setVendeur($this->getUser());
            $jeu->setDateCreation(new \DateTime());
            $jeu->setSlug($slugger->slug($jeu->getTitre())->lower()->toString());
            $jeu->setStatut(JeuStatut::EN_VENTE);

            // Photo envoyée depuis l'ordinateur, si elle existe
            $this->enregistrerPhoto($form, $jeu);

            $em->persist($jeu);
            $em->flush();

            // Alerte de retour en stock pour les acheteurs qui ont ce titre sur cette plateforme
            $alertes->miseEnVenteNouvelle($jeu);

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

        // Un jeu vendu ne peut pas être supprimé : il est lié à une commande
        if ($jeu->getStatut() === JeuStatut::VENDU) {
            $this->addFlash('error', 'Un jeu vendu ne peut pas être supprimé : il est lié à une commande.');
            return $this->redirectToRoute('app_ventes', ['vendeurId' => $vendeurId]);
        }

        $em->remove($jeu);
        $em->flush();

        $this->addFlash('success', 'Le jeu a bien été supprimé.');

        return $this->redirectToRoute('app_ventes', ['vendeurId' => $vendeurId]);
    }

    #[Route('/ventes/{vendeurId}', name: 'app_ventes', requirements: ['vendeurId' => '\d+'])]
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

    /**
     * Déplace la photo envoyée dans public/uploads/jeux/ et enregistre son chemin dans $jeu->image.
     * Ne fait rien si aucune photo n'a été envoyée : l'image actuelle est alors conservée.
     */
    private function enregistrerPhoto(FormInterface $form, Jeu $jeu): void
    {
        /** @var UploadedFile|null $fichier */
        $fichier = $form->get('imageFichier')->getData();

        if (!$fichier) {
            return;
        }

        // Nom unique pour éviter d'écraser une autre photo
        $nom = bin2hex(random_bytes(8)) . '.' . $fichier->guessExtension();
        $dossier = $this->getParameter('kernel.project_dir') . '/public/uploads/jeux';

        try {
            $fichier->move($dossier, $nom);
        } catch (FileException) {
            $this->addFlash('danger', 'La photo n\'a pas pu être enregistrée. Réessaie.');
            return;
        }

        // Chemin public, utilisable directement dans <img src="...">
        $jeu->setImage('/uploads/jeux/' . $nom);
    }
}