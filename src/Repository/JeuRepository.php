<?php

namespace App\Repository;

use App\Entity\Jeu;
use App\Enum\Etat;
use App\Enum\JeuStatut;
use App\Enum\Plateforme;
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

    public function getLastJeux(int $limit = 10, bool $only_disponible = false): array {

        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.masque = false')
            ->orderBy('j.date_creation', 'DESC')
            ->leftJoin('j.vendeur', 'v')
            ->addSelect('v')
            ->setMaxResults($limit);

        if ($only_disponible) {
            $qb->andWhere('j.statut = :statut')
               ->setParameter('statut', JeuStatut::EN_VENTE);
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
            ->select('COUNT(j.id)')
            ->andWhere('j.masque = false');

        if ($only_disponible) {
            $qb->andWhere('j.statut = :statut')
               ->setParameter('statut', JeuStatut::EN_VENTE);
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
            ->andWhere('j.masque = false')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $categorieSlug)
            ->getQuery()
            ->getResult();
    }

    // Jeux à 15 € maximum (affichés sur l'accueil)
    public function findPetitsPrix(int $valeurMax = 15, bool $only_disponible = false): array
    {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.categories', 'c')
            ->addSelect('c')
            ->leftJoin('j.vendeur', 'v')
            ->addSelect('v')
            ->andWhere('j.masque = false')
            ->andWhere('j.prix <= :prix')
            ->setParameter('prix', $valeurMax)
            ->setMaxResults(10);

        if ($only_disponible) {
            $qb->andWhere('j.statut = :statut')
               ->setParameter('statut', JeuStatut::EN_VENTE);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Liste paginée des jeux selon les filtres.
     * Les jeux disponibles passent avant les jeux vendus, puis le tri choisi s'applique.
     *
     * @param array $filtres      ex. ['query' => 'zelda', 'categorie' => 'rpg', 'prix_min' => 10, 'prix_max' => 50,
     *                            'pegi' => 18, 'plateforme' => 'ps5', 'etat' => 'neuf', 'tri' => 'prix_asc']
     * @param int   $numeroPage   la page demandée (1 = première page)
     * @param int   $parPage      nombre de jeux affichés par page
     *
     * @return Paginator          la liste de la page, et le total via count()
     */
    public function trouverFiltres(array $filtres, int $numeroPage = 1, int $parPage = 12): Paginator
    {
        $requete = $this->createQueryBuilder('j');

        // Annonces masquées exclues des résultats
        $requete->andWhere('j.masque = false');

        // Tri choisi par l'utilisateur (par défaut : les plus récents)
        $ordre = match ($filtres['tri'] ?? '') {
            'prix_asc'  => ['j.prix', 'ASC'],
            'prix_desc' => ['j.prix', 'DESC'],
            default     => ['j.date_creation', 'DESC'],
        };

        $requete->orderBy("CASE WHEN j.statut = '" . JeuStatut::VENDU->value . "' THEN 1 ELSE 0 END", 'ASC')
            ->addOrderBy($ordre[0], $ordre[1]);

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

        // Plateforme : on vérifie que la valeur existe bien dans l'enum
        if (!empty($filtres['plateforme']) && ($plateforme = Plateforme::tryFrom($filtres['plateforme'])) !== null) {
            $requete->andWhere('j.plateforme = :plateforme')
                ->setParameter('plateforme', $plateforme);
        }

        // État du jeu
        if (!empty($filtres['etat']) && ($etat = Etat::tryFrom($filtres['etat'])) !== null) {
            $requete->andWhere('j.etat = :etat')
                ->setParameter('etat', $etat);
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