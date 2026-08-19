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

        public function countJeux(bool $only_disponible = false): int
        {
            $qb = $this->createQueryBuilder('j')
                ->select('COUNT(j.id)');

            if ($only_disponible) {
                $qb->andWhere('j.statut = :statut')
                   ->setParameter('statut', 'disponible');
            }

            return (int) $qb->getQuery()->getSingleScalarResult();
        }

        public function findAllByCategorie(string $categorieSlug): array
        {
            return $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $categorieSlug)
                ->getQuery()
                ->getResult();
        }

        //Prix de 15€ max
        public function findPetitsPrix(int $valeurMax = 15): array
        {
            return $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->andWhere('j.prix <= :prix')
                ->setParameter('prix', $valeurMax)
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        }

        public function findAllByRecherche(array $criteres = []): array
        {
            $qb = $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->distinct();

            if (!empty($criteres['query'])) {
                $qb->andWhere('j.titre LIKE :query OR j.description LIKE :query OR c.nom LIKE :query OR v.pseudo LIKE :query')
                ->setParameter('query', '%' . $criteres['query'] . '%');
            }

            if (!empty($criteres['categorySlug'])) {
                $qb->andWhere('c.slug = :categorySlug')
                ->setParameter('categorySlug', $criteres['categorySlug']);
            }

            if (!empty($criteres['prixMax'])) {
                $qb->andWhere('j.prix <= :prixMax')
                ->setParameter('prixMax', $criteres['prixMax']);
            }

            if (!empty($criteres['prixMin'])) {
                $qb->andWhere('j.prix >= :prixMin')
                ->setParameter('prixMin', $criteres['prixMin']);
            }

            if (!empty($criteres['pegi'])) {
                $qb->andWhere('j.pegi = :pegi')
                ->setParameter('pegi', $criteres['pegi']);
            }

            return $qb->getQuery()->getResult();
        }

        public function getAll(): array
        {
            return $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->getQuery()
                ->getResult();
        }
}
