<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventSubscription;
use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/poll', name: 'app_notification_poll', methods: ['GET'])]
    public function poll(NotificationService $ns): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['unread' => 0, 'notifications' => []]);

        // Heartbeat check
        $ns->checkAndGenerate($user);
        
        $unread = $ns->getUnreadCount($user);
        $recent = $ns->getRecentNotifications($user);
        
        $data = [];
        foreach ($recent as $n) {
            $data[] = [
                'id'      => $n->getId(),
                'title'   => $n->getTitle(),
                'message' => $n->getMessage(),
                'time'    => $n->getCreatedAt()->format('H:i'),
                'isRead'  => $n->isRead(),
                'eventId' => $n->getEventId(),
            ];
        }

        return new JsonResponse([
            'unread' => $unread,
            'notifications' => $data
        ]);
    }

    #[Route('/subscribe/{id}', name: 'app_notification_subscribe', methods: ['POST'])]
    public function toggleSubscribe(Event $event, EntityManagerInterface $em, NotificationService $ns): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Login required'], 401);

        $subRepo = $em->getRepository(EventSubscription::class);
        $existing = $subRepo->findOneBy(['user' => $user, 'event' => $event]);

        if ($existing) {
            $em->remove($existing);
            $subscribed = false;
        } else {
            $sub = new EventSubscription();
            $sub->setUser($user);
            $sub->setEvent($event);
            $em->persist($sub);
            $subscribed = true;

            // 🔔 IMMEDIATE FEEDBACK
            $ns->createUniqueNotification(
                $user, 
                $event, 
                'SUBSCRIBED', 
                "Subscription Confirmed", 
                "You will now receive updates for '{$event->getTitle()}'."
            );
        }

        $em->flush();

        return new JsonResponse(['subscribed' => $subscribed]);
    }

    #[Route('/read/{id}', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $notification->setIsRead(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/clear-all', name: 'app_notification_clear_all', methods: ['POST'])]
    public function clearAll(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Login required'], 401);

        $repo = $em->getRepository(Notification::class);
        $notifications = $repo->findBy(['user' => $user]);

        foreach ($notifications as $n) {
            $em->remove($n);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/status/{id}', name: 'app_notification_status', methods: ['GET'])]
    public function checkSubscription(Event $event, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['subscribed' => false]);

        $sub = $em->getRepository(EventSubscription::class)->findOneBy(['user' => $user, 'event' => $event]);
        return new JsonResponse(['subscribed' => !!$sub]);
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $notifications = $notificationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/read-all', name: 'app_notification_read_all', methods: ['POST'])]
    public function markAllAsRead(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
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
