<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    // Nombre de notifications non lues, pour le badge de la cloche
    public function compterNonLues(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.lue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Dernières notifications de l'utilisateur, les plus récentes en premier
    public function trouverPourUser(User $user, int $limite = 50): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.dateCreation', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    // Marque toutes les notifications de l'utilisateur comme lues
    public function marquerToutesLues(User $user): void
    {
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.lue', 'true')
            ->where('n.user = :user')
            ->andWhere('n.lue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}