<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Fournit au header la fonction notifications_non_lues() pour le badge de la cloche
class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notifications_non_lues', [$this, 'nombreNonLues']),
        ];
    }

    public function nombreNonLues(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->notificationRepository->compterNonLues($user);
    }
}