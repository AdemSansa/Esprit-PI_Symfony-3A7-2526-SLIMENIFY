<?php

namespace App\Controller;

use App\Entity\Notifications;
use App\Repository\NotificationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationsController extends AbstractController
{
    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationsRepository $notificationsRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notifications $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(NotificationsRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_notification_index');
    }
}