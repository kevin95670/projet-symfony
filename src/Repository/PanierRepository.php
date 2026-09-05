<?php

namespace App\Repository;

use App\Entity\User;

use App\Entity\Panier;
use App\Entity\Commande;
use App\Entity\CommandeLignes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    //    /**
    //     * @return Panier[] Returns an array of Panier objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Panier
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

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

    public function checkout(Panier $panier): Commande
    {
        $commande = new Commande();
        $commande->setUser($panier->getUser());
        $commande->setDateCommande(new \DateTimeImmutable());
        $commande->setStatut('payée');
        $commande->setReference(uniqid('CMD-'));

        foreach ($panier->getPanierItems() as $panierItem) {
            $commandeItem = new CommandeLignes();
            $commandeItem->setCommande($commande);
            $commandeItem->setJeu($panierItem->getJeu());
            $commandeItem->setQuantite($panierItem->getQuantite());
            $commandeItem->setPrixUnitaire($panierItem->getJeu()->getPrix()); // fige le prix au moment de l'achat

            $commande->addCommandeLigne($commandeItem);
            $this->getEntityManager()->persist($commandeItem);
        }

        $this->getEntityManager()->persist($commande);

        // Vide le panier après la commande
        foreach ($panier->getPanierItems() as $panierItem) {
            $this->getEntityManager()->remove($panierItem);
        }

        $this->getEntityManager()->flush();

        return $commande;
    }
}
