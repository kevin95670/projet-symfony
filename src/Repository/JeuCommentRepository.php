<?php

namespace App\Repository;

use App\Entity\Jeu;
use App\Entity\JeuComment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JeuComment>
 */
class JeuCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JeuComment::class);
    }

    // Messages d'un fil : entre un jeu et un acheteur, du plus ancien au plus récent
    public function messagesDuFil(Jeu $jeu, User $acheteur): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.jeu = :jeu')
            ->andWhere('c.acheteur = :acheteur')
            ->setParameter('jeu', $jeu)
            ->setParameter('acheteur', $acheteur)
            ->orderBy('c.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Acheteurs qui ont ouvert un fil sur ce jeu (vue du vendeur)
    public function acheteursAvecMessages(Jeu $jeu): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT DISTINCT u FROM App\Entity\User u
             JOIN App\Entity\JeuComment c WITH c.acheteur = u
             WHERE c.jeu = :jeu
             ORDER BY u.pseudo ASC'
        )
            ->setParameter('jeu', $jeu)
            ->getResult();
    }
}