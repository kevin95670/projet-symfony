<?php

namespace App\Controller;

use App\Entity\CommandeLignes;
use App\Entity\Jeu;
use App\Entity\User;
use App\Enum\JeuStatut;
use App\Enum\StatutLigne;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TableauDeBordController extends AbstractController
{
    // Page récapitulative du vendeur : jeux, chiffre d'affaires, expéditions à faire, avis
    #[Route('/mon-compte/tableau-de-bord', name: 'app_tableau_de_bord')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Jeux du vendeur
        $jeux = $user->getJeux();
        $jeuxEnVente = $jeux->filter(fn (Jeu $jeu) => $jeu->getStatut() === JeuStatut::EN_VENTE)->count();
        $jeuxVendus = $jeux->filter(fn (Jeu $jeu) => $jeu->getStatut() === JeuStatut::VENDU)->count();

        // Lignes de commande de ses jeux : une ligne = un jeu vendu
        $lignes = $em->getRepository(CommandeLignes::class)
            ->createQueryBuilder('l')
            ->join('l.jeu', 'j')
            ->addSelect('j')
            ->join('l.commande', 'c')
            ->addSelect('c')
            ->andWhere('j.vendeur = :vendeur')
            ->setParameter('vendeur', $user)
            ->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();

        $chiffreAffaires = 0.0;
        $aExpedier = [];
        $nombreExpediees = 0;
        $nombreRecues = 0;

        foreach ($lignes as $ligne) {
            $chiffreAffaires += (float) $ligne->getPrixUnitaire() * $ligne->getQuantite();

            if ($ligne->getStatut() === StatutLigne::EN_PREPARATION) {
                $aExpedier[] = $ligne;
            } elseif ($ligne->getStatut() === StatutLigne::EXPEDIEE) {
                $nombreExpediees++;
            } else {
                $nombreRecues++;
            }
        }

        // Avis reçus par le vendeur
        $avis = $user->getAvisRecus();
        $nombreAvis = $avis->count();
        $noteMoyenne = null;
        if ($nombreAvis > 0) {
            $total = 0;
            foreach ($avis as $unAvis) {
                $total += $unAvis->getNote();
            }
            $noteMoyenne = round($total / $nombreAvis, 1);
        }

        return $this->render('user/tableau_de_bord.html.twig', [
            'jeuxEnVente' => $jeuxEnVente,
            'jeuxVendus' => $jeuxVendus,
            'chiffreAffaires' => $chiffreAffaires,
            'aExpedier' => $aExpedier,
            'nombreExpediees' => $nombreExpediees,
            'nombreRecues' => $nombreRecues,
            'nombreAvis' => $nombreAvis,
            'noteMoyenne' => $noteMoyenne,
        ]);
    }
}