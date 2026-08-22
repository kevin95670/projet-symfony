<?php

namespace App\Repository;

use App\Entity\Jeu;
use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    //    /**
    //     * @return WishlistItem[] Returns an array of WishlistItem objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WishlistItem
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

        public function getWishlistItemsByUserId(int $userId): array {
            return $this->createQueryBuilder('w')
                ->leftJoin('w.jeu', 'j')
                ->addSelect('j')
                ->where('w.user = :userId')
                ->setParameter('userId', $userId)
                ->getQuery()
                ->getResult();
        }

        public function addToWishlist(int $userId, int $jeuId): void {
            $entityManager = $this->getEntityManager();

            $wishlistItem = new WishlistItem();
            $wishlistItem->setUser($entityManager->getReference('App\Entity\User', $userId));
            $wishlistItem->setJeu($entityManager->getReference('App\Entity\Jeu', $jeuId));
            $wishlistItem->setDateAjout(new \DateTimeImmutable());

            $entityManager->persist($wishlistItem);
            $entityManager->flush();
        }

        public function removeFromWishlist(int $userId, int $jeuId): void {
            $entityManager = $this->getEntityManager();

            $wishlistItem = $this->createQueryBuilder('w')
                ->where('w.user = :userId')
                ->andWhere('w.jeu = :jeuId')
                ->setParameter('userId', $userId)
                ->setParameter('jeuId', $jeuId)
                ->getQuery()
                ->getOneOrNullResult();

            if ($wishlistItem) {
                $entityManager->remove($wishlistItem);
                $entityManager->flush();
            }
        }

        public function deleteFromWishlist(int $userId, int $jeuId): void {
            $entityManager = $this->getEntityManager();

            $wishlistItem = $this->createQueryBuilder('w')
                ->where('w.user = :userId')
                ->andWhere('w.jeu = :jeuId')
                ->setParameter('userId', $userId)
                ->setParameter('jeuId', $jeuId)
                ->getQuery()
                ->getOneOrNullResult();

            if ($wishlistItem) {
                $entityManager->remove($wishlistItem);
                $entityManager->flush();
            }
        }

        public function findOneByUserAndJeu(User $user, Jeu $jeu): ?WishlistItem
        {
            return $this->createQueryBuilder('w')
                ->andWhere('w.user = :user')
                ->andWhere('w.jeu = :jeu')
                ->setParameter('user', $user)
                ->setParameter('jeu', $jeu)
                ->getQuery()
                ->getOneOrNullResult();
        }

        public function findJeuIdsByUser(User $user): array
{
            $result = $this->createQueryBuilder('w')
                ->select('IDENTITY(w.jeu) as jeuId')
                ->andWhere('w.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getScalarResult();

            return array_column($result, 'jeuId');
        }
}
