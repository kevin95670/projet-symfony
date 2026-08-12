<?php

namespace App\Repository;

use App\Entity\Jeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Jeu>
 */
class JeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jeu::class);
    }

    //    /**
    //     * @return Jeu[] Returns an array of Jeu objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('j.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Jeu
    //    {
    //        return $this->createQueryBuilder('j')
    //            ->andWhere('j.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

        //    /**
        //     * @return Jeu[] Returns an array of Jeu objects
        //     */
        public function getLastJeux(int $limit = 10, bool $only_disponible = false): array {
            
            $qb = $this->createQueryBuilder('j')
                ->orderBy('j.date_creation', 'DESC')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->setMaxResults($limit);

            if ($only_disponible) {
                $qb->andWhere('j.statut = :statut')
                   ->setParameter('statut', 'disponible');
            }

            return $qb->getQuery()->getResult();
        }

        public function findOneBySlug(string $slug): ?Jeu
        {
            return $this->createQueryBuilder('j')
                ->andWhere('j.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getOneOrNullResult();
        }
}
