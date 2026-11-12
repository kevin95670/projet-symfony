<?php

namespace App\Tests\Repository;

use App\Entity\Commande;
use App\Entity\PanierItem;
use App\Enum\JeuStatut;
use App\Exception\JeuIndisponibleException;
use App\Repository\PanierRepository;
use App\Tests\Support\DonneesAchatTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Teste directement la logique de vente (PanierRepository::checkout),
 * sans passer par Stripe ni par le navigateur.
 */
final class PanierRepositoryCheckoutTest extends KernelTestCase
{
    use DonneesAchatTrait;

    private EntityManagerInterface $em;
    private PanierRepository $panierRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->panierRepository = static::getContainer()->get(PanierRepository::class);
    }

    public function testCheckoutCreeLaCommandeVendLeJeuEtVideLePanier(): void
    {
        $vendeur = $this->creerUtilisateur($this->em);
        $acheteur = $this->creerUtilisateur($this->em);
        $jeu = $this->creerJeu($this->em, $vendeur, '40.00');
        $panier = $this->creerPanier($this->em, $acheteur, [$jeu]);
        $this->em->flush();

        $adresse = [
            'nom' => 'Test Utilisateur',
            'ligne1' => '1 rue du Test',
            'ligne2' => null,
            'code_postal' => '75001',
            'ville' => 'Paris',
            'pays' => 'FR',
        ];

        $commande = $this->panierRepository->checkout($panier, $adresse);

        $this->assertInstanceOf(Commande::class, $commande);
        $this->assertCount(1, $commande->getCommandeLignes());
        $this->assertSame(JeuStatut::VENDU, $jeu->getStatut());
        $this->assertSame($adresse, $acheteur->getAdresseLivraison());
        $this->assertSame(0, $this->em->getRepository(PanierItem::class)->count([]));
    }

    public function testCheckoutRefuseUnJeuDejaVendu(): void
    {
        $vendeur = $this->creerUtilisateur($this->em);
        $acheteur = $this->creerUtilisateur($this->em);
        $jeu = $this->creerJeu($this->em, $vendeur);
        $panier = $this->creerPanier($this->em, $acheteur, [$jeu]);
        $jeu->setStatut(JeuStatut::VENDU);
        $this->em->flush();

        $this->expectException(JeuIndisponibleException::class);

        $this->panierRepository->checkout($panier);
    }

    public function testCheckoutRefuseUnJeuMasque(): void
    {
        $vendeur = $this->creerUtilisateur($this->em);
        $acheteur = $this->creerUtilisateur($this->em);
        $jeu = $this->creerJeu($this->em, $vendeur);
        $panier = $this->creerPanier($this->em, $acheteur, [$jeu]);
        $jeu->setMasque(true);
        $this->em->flush();

        $this->expectException(JeuIndisponibleException::class);

        $this->panierRepository->checkout($panier);
    }
}