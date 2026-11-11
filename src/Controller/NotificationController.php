<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications')]
    #[IsGranted('ROLE_USER')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // On charge la liste avant de marquer comme lues, pour garder le repère des nouvelles
        $notifications = $notificationRepository->trouverPourUser($user);
        $notificationRepository->marquerToutesLues($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}