<?php

namespace App\Repository;

use App\Entity\Jeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
        public function findPetitsPrix(int $valeurMax = 15, $only_disponible = false): array
        {
            $qb = $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->andWhere('j.prix <= :prix')
                ->setParameter('prix', $valeurMax)
                ->setMaxResults(10);

            if ($only_disponible) {
                $qb->andWhere('j.statut = :statut')
                   ->setParameter('statut', 'disponible');
            }

            return $qb->getQuery()->getResult();
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

        /**
         * Liste des jeux de la boutique, découpée en pages, avec les filtres.
         *
         * @param array $filtres      ex. ['query' => 'zelda', 'categorie' => 'rpg', 'prix_min' => 10, 'prix_max' => 50, 'pegi' => 18]
         * @param int   $numeroPage   la page demandée (1 = première page)
         * @param int   $parPage      nombre de jeux affichés par page
         *
         * @return Paginator          la liste de la page, et le total via count()
         */
        public function trouverFiltres(array $filtres, int $numeroPage = 1, int $parPage = 12): Paginator
        {
            // On commence par la requête de base : tous les jeux, les plus récents en premier
            $requete = $this->createQueryBuilder('j')
                ->orderBy('j.date_creation', 'DESC');

            // Filtre par catégorie (on joint la table des catégories)
            if (!empty($filtres['categorie'])) {
                $requete->innerJoin('j.categories', 'c')
                    ->andWhere('c.slug = :categorie')
                    ->setParameter('categorie', $filtres['categorie']);
            }

            // Recherche dans le titre, la description, le pseudo du vendeur et le nom des catégories
            if (!empty($filtres['query'])) {
                $requete->leftJoin('j.vendeur', 'v')
                    ->andWhere('j.titre LIKE :recherche
                        OR j.description LIKE :recherche
                        OR v.pseudo LIKE :recherche
                        OR j.id IN (
                            SELECT j2.id FROM App\Entity\Jeu j2
                            JOIN j2.categories c2
                            WHERE c2.nom LIKE :recherche
                        )')
                    ->setParameter('recherche', '%' . $filtres['query'] . '%');
            }

            // Prix minimum
            if (!empty($filtres['prix_min'])) {
                $requete->andWhere('j.prix >= :prixMin')
                    ->setParameter('prixMin', $filtres['prix_min']);
            }

            // Prix maximum
            if (!empty($filtres['prix_max'])) {
                $requete->andWhere('j.prix <= :prixMax')
                    ->setParameter('prixMax', $filtres['prix_max']);
            }

            // Classification PEGI
            if (!empty($filtres['pegi'])) {
                $requete->andWhere('j.pegi = :pegi')
                    ->setParameter('pegi', $filtres['pegi']);
            }

            // Pagination : on saute les jeux des pages précédentes, puis on en prend $parPage
            $requete->setFirstResult(($numeroPage - 1) * $parPage)
                ->setMaxResults($parPage);

            return new Paginator($requete);
        }


        public function findAllVentes(int $vendeurId): array
        {
            return $this->createQueryBuilder('j')
                ->leftJoin('j.categories', 'c')
                ->addSelect('c')
                ->leftJoin('j.vendeur', 'v')
                ->addSelect('v')
                ->andWhere('j.vendeur = :vendeur')
                ->setParameter('vendeur', $vendeurId)
                ->getQuery()
                ->getResult();
        }
}