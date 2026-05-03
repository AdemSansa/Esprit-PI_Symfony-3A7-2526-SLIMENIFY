<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventSubscription;
use App\Entity\Notification;
use App\Entity\Registration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Heartbeat check: scans subscribed and registered events and generates notifications
     */
    public function checkAndGenerate(User $user): void
    {
        $userEmail = $user->getEmail();
        
        // 1. Get explicitly subscribed events
        $subscriptions = $this->em->getRepository(EventSubscription::class)->findBy(['user' => $user]);
        $events = array_map(fn($s) => $s->getEvent(), $subscriptions);
        
        // 2. ALSO Get events from registrations (matching user email)
        if ($userEmail) {
            $registrations = $this->em->getRepository(Registration::class)->findBy(['participantEmail' => $userEmail]);
            foreach ($registrations as $reg) {
                $e = $reg->getEvent();
                if (!in_array($e, $events, true)) {
                    $events[] = $e;
                }
            }
        }
        
        $now = new \DateTime();
        
        foreach ($events as $event) {
            if (!$event) continue;

            $status = $event->getTimeStatus();
            
            // 1. Check for "Starting Soon" (within 3 days)
            if ($event->getDateStart() > $now) {
                $diff = $now->diff($event->getDateStart());
                if ($diff->days < 3) {
                    $this->createUniqueNotification($user, $event, 'STARTING_SOON', "Starting Soon", "The event '{$event->getTitle()}' is starting in " . ($diff->days == 0 ? "less than 24 hours" : $diff->days . " days") . "!");
                }
            }
            
            if ($status === 'started') {
                $this->createUniqueNotification($user, $event, 'STARTED', "Live Now", "The event '{$event->getTitle()}' has started!");
            }
            
            if ($status === 'ended') {
                $start = $event->getDateStart();
                /** @var \DateTime $start */
                $endDateTime = $event->getDateEnd() ?: (clone $start)->modify('+3 hours');
                $diffEnd = $now->diff($endDateTime);
                
                if ($diffEnd->days < 3) {
                    $this->createUniqueNotification($user, $event, 'ENDED', "Event Ended", "The event '{$event->getTitle()}' has ended.");
                }
            }
        }
        
        $this->em->flush();
    }

    public function createUniqueNotification(User $user, Event $event, string $type, string $title, string $message): void
    {
        $repo = $this->em->getRepository(Notification::class);
        $existing = $repo->findOneBy([
            'user' => $user,
            'eventId' => $event->getId(),
            'type' => $type
        ]);

        if (!$existing) {
            $notification = new Notification();
            $notification->setUser($user);
            $notification->setEventId($event->getId());
            $notification->setType($type);
            $notification->setTitle($title);
            $notification->setMessage($message);
            $this->em->persist($notification);
        }
    }

    public function getUnreadCount(User $user): int
    {
        return $this->em->getRepository(Notification::class)->count(['user' => $user, 'isRead' => false]);
    }

    /**
     * @return Notification[]
     */
    public function getRecentNotifications(User $user, int $limit = 5): array
    {
        return $this->em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function notifyUserByEmail(string $email, Event $event, string $type, string $title, string $message): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $this->createUniqueNotification($user, $event, $type, $title, $message);
        }
    }

    public function notifyUserById(int $userId, Event $event, string $type, string $title, string $message): void
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if ($user) {
            $this->createUniqueNotification($user, $event, $type, $title, $message);
        }
    }
}
