<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use App\Repository\CategorieRepository;
use App\Enum\Pegi;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Jeu;
use App\Form\JeuForm;
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
    public function apercu_jeu(JeuRepository $jeuRepository, string $slug): Response
    {
        $jeu = $jeuRepository->findOneBySlug($slug);

        return $this->render('jeu/index.html.twig', [
            'controller_name' => 'JeuController',
            'jeu' => $jeu,
        ]);
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
}
