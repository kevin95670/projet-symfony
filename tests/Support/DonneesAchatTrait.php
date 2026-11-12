<?php

namespace App\Tests\Support;

use App\Entity\Jeu;
use App\Entity\Panier;
use App\Entity\PanierItem;
use App\Entity\User;
use App\Enum\Etat;
use App\Enum\JeuStatut;
use App\Enum\Pegi;
use App\Enum\Plateforme;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fabrique des données de test (utilisateurs, jeux, paniers) avec des valeurs fictives.
 * Les tests n'ont besoin que de ces petites fonctions, pas de fixtures.
 */
trait DonneesAchatTrait
{
    private function creerUtilisateur(EntityManagerInterface $em): User
    {
        $user = (new User())
            ->setEmail('test-' . uniqid() . '@exemple.com')
            ->setPassword('mot-de-passe-fictif')
            ->setPrenom('Test')
            ->setNom('Utilisateur')
            ->setPseudo('pseudo' . uniqid())
            ->setRoles(['ROLE_USER'])
            ->setDateNaissance(new \DateTime('1990-01-01'))
            ->setDateCreation(new \DateTime());

        $em->persist($user);

        return $user;
    }

    private function creerJeu(EntityManagerInterface $em, User $vendeur, string $prix = '40.00'): Jeu
    {
        $jeu = (new Jeu())
            ->setTitre('Jeu de test ' . uniqid())
            ->setDescription('Description fictive')
            ->setPrix($prix)
            ->setPlateforme(Plateforme::cases()[0])
            ->setEtat(Etat::cases()[0])
            ->setPegi(Pegi::cases()[0])
            ->setVendeur($vendeur)
            ->setStatut(JeuStatut::EN_VENTE)
            ->setSlug('jeu-test-' . uniqid())
            ->setDateCreation(new \DateTime())
            ->setMiseAJour(new \DateTimeImmutable());

        $em->persist($jeu);

        return $jeu;
    }

    /**
     * @param Jeu[] $jeux
     */
    private function creerPanier(EntityManagerInterface $em, User $acheteur, array $jeux): Panier
    {
        $panier = (new Panier())
            ->setUser($acheteur)
            ->setDateCreation(new \DateTimeImmutable())
            ->setMiseAJour(new \DateTimeImmutable());

        $acheteur->setPanier($panier);
        $em->persist($panier);

        foreach ($jeux as $jeu) {
            $item = (new PanierItem())
                ->setPanier($panier)
                ->setJeu($jeu)
                ->setQuantite(1)
                ->setMiseAJour(new \DateTimeImmutable());

            $em->persist($item);
            $panier->addPanierItem($item);
        }

        return $panier;
    }
}