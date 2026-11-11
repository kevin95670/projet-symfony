<?php

namespace App\Repository;

use App\Entity\User;

use App\Entity\Panier;
use App\Entity\Commande;
use App\Entity\CommandeLignes;
use App\Entity\WishlistItem;
use App\Enum\JeuStatut;
use App\Exception\JeuIndisponibleException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    public function viderPanier(Panier $panier): void {
        $entityManager = $this->getEntityManager();

        foreach ($panier->getPanierItems() as $panierItem) {
            $entityManager->remove($panierItem);
        }

        $entityManager->flush();
    }

    public function findOrCreateForUser(User $user): Panier
    {
        $panier = $this->findOneBy(['user' => $user]);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUser($user);
            $panier->setDateCreation(new \DateTimeImmutable());
            $panier->setMiseAJour(new \DateTimeImmutable());
            $this->getEntityManager()->persist($panier);
            $this->getEntityManager()->flush();
        }

        return $panier;
    }

    // Crée la commande. Lève JeuIndisponibleException si un jeu n'est plus en vente.
    // $livraison : adresse saisie chez Stripe (peut être null)
    public function checkout(Panier $panier, ?array $livraison = null): Commande
    {
        $em = $this->getEntityManager();
        $acheteur = $panier->getUser();

        // Tout se passe dans une transaction : si une erreur survient, rien n'est enregistré
        return $em->wrapInTransaction(function () use ($em, $panier, $acheteur, $livraison) {

            // Verrouille chaque jeu : deux paiements simultanés ne peuvent pas vendre le même jeu
            foreach ($panier->getPanierItems() as $panierItem) {
                $jeu = $panierItem->getJeu();
                $em->lock($jeu, LockMode::PESSIMISTIC_WRITE);
                $em->refresh($jeu); // on relit le statut depuis la base, après le verrou

                if ($jeu->getStatut() !== JeuStatut::EN_VENTE) {
                    throw new JeuIndisponibleException($jeu);
                }
            }

            $commande = new Commande();
            $commande->setUser($acheteur);
            $commande->setDateCommande(new \DateTimeImmutable());
            $commande->setStatut('payée');
            $commande->setReference(uniqid('CMD-'));
            $commande->setLivraison($livraison);

            foreach ($panier->getPanierItems() as $panierItem) {
                $commandeItem = new CommandeLignes();
                $commandeItem->setCommande($commande);
                $commandeItem->setJeu($panierItem->getJeu());
                $commandeItem->setQuantite($panierItem->getQuantite());
                $commandeItem->setPrixUnitaire($panierItem->getJeu()->getPrix()); // fige le prix au moment de l'achat

                $commande->addCommandeLigne($commandeItem);
                $em->persist($commandeItem);
            }

            // Le jeu est vendu : il ne doit plus apparaître comme disponible
            foreach ($panier->getPanierItems() as $panierItem) {
                $jeu = $panierItem->getJeu();
                $jeu->setStatut(JeuStatut::VENDU);

                // Retirer le jeu de la wishlist de l'acheteur
                // (les paniers des autres acheteurs gardent le jeu, affiché comme « Vendu » et non payable)
                $wishlistItems = $em->getRepository(WishlistItem::class)->findBy(['user' => $acheteur, 'jeu' => $jeu]);
                foreach ($wishlistItems as $wishlistItem) {
                    $em->remove($wishlistItem);
                }
            }

            $em->persist($commande);

            // Vide le panier de l'acheteur après la commande
            foreach ($panier->getPanierItems() as $panierItem) {
                $em->remove($panierItem);
            }

            $em->flush();

            return $commande;
        });
    }
}