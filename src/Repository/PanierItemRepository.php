<?php

namespace App\Repository;

use App\Entity\PanierItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Jeu;
use App\Entity\Panier;

/**
 * @extends ServiceEntityRepository<PanierItem>
 */
class PanierItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PanierItem::class);
    }

    //    /**
    //     * @return PanierItem[] Returns an array of PanierItem objects
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

    //    public function findOneBySomeField($value): ?PanierItem
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findOneByPanierAndJeu(Panier $panier, Jeu $jeu): ?PanierItem
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.panier = :panier')
            ->andWhere('p.jeu = :jeu')
            ->setParameter('panier', $panier)
            ->setParameter('jeu', $jeu)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function deleteFromCart(int $panierId, int $jeuId): void {
        $entityManager = $this->getEntityManager();

        $panierItem = $this->createQueryBuilder('p')
            ->where('p.panier = :panierId')
            ->andWhere('p.jeu = :jeuId')
            ->setParameter('panierId', $panierId)
            ->setParameter('jeuId', $jeuId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($panierItem) {
            $entityManager->remove($panierItem);
            $entityManager->flush();
        }
    }

    public function addToCart(int $panierId, int $jeuId): void {
        $entityManager = $this->getEntityManager();

        $panierItem = new PanierItem();
        $panierItem->setPanier($entityManager->getReference('App\Entity\Panier', $panierId));
        $panierItem->setJeu($entityManager->getReference('App\Entity\Jeu', $jeuId));
        $panierItem->setMiseAJour(new \DateTimeImmutable());
        $panierItem->setQuantite(1);

        $entityManager->persist($panierItem);
        $entityManager->flush();
    }
}
