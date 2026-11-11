<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

// Crée une notification pour un utilisateur. À appeler depuis les contrôleurs ou le webhook.
class NotificationService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function creer(User $destinataire, string $message, ?string $lien = null): void
    {
        $notification = new Notification();
        $notification->setUser($destinataire);
        $notification->setMessage($message);
        $notification->setLien($lien);
        $notification->setDateCreation(new \DateTimeImmutable());

        $this->em->persist($notification);
        $this->em->flush();
    }
}