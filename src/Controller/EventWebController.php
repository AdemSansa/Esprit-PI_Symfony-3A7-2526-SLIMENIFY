<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/events')]
class EventWebController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, RegistrationRepository $registrationRepository, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('q', '');
        $format = $request->query->get('format', 'all');
        $mine = $request->query->getBoolean('mine', false);
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Build query
        $qb = $eventRepository->createQueryBuilder('e');
        
        if ($mine && $user instanceof \App\Entity\User) {
            // Filter to show only user's events
            $qb->andWhere('e.organizerId = :organizerId')
               ->setParameter('organizerId', $user->getId());
        }
        
        if ($query) {
            $qb->andWhere('e.title LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($format !== 'all') {
            $qb->andWhere('e.type = :format')
               ->setParameter('format', $format);
        }
        
        $queryBuilder = $qb->orderBy('e.dateStart', 'ASC')
                     ->getQuery();

        $events = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            4 // Items per page
        );

        // 🌟 ANTICIPATED EVENTS (Top 5 Imminent)
        $anticipatedEvents = $eventRepository->createQueryBuilder('e')
            ->andWhere('e.dateStart >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateStart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        // 🎫 PERSONALIZED REGISTRATION TRACKING
        $userRegistrations = [];
        if ($user instanceof \App\Entity\User) {
            $registrations = $registrationRepository->findBy(['participantEmail' => $user->getEmail()]);
            foreach ($registrations as $reg) {
                $userRegistrations[$reg->getEvent()->getId()] = [
                    'status' => $reg->getStatus(),
                    'id' => $reg->getId()
                ];
            }
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'anticipatedEvents' => $anticipatedEvents,
            'searchQuery' => $query,
            'currentFormat' => $format,
            'isMine' => $mine,
            'userRegistrations' => $userRegistrations,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Only therapists and admins can create events
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImageUrl('uploads/events/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
            }

            // Set organizer ID
            /** @var \App\Entity\User|null $user */
            $user = $this->getUser();
            if ($user instanceof \App\Entity\User) {
                $event->setOrganizerId($user->getId());
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event, RegistrationRepository $registrationRepository, EventRepository $eventRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $userRegistration = null;


        if ($user instanceof \App\Entity\User) {
            // Check if the current user is already registered
            $userRegistration = $registrationRepository->findOneBy([
                'event' => $event,
                'participantEmail' => $user->getEmail()
            ]);
        }

        // 🌟 FETCH RELATED EVENTS (Exclude current, show upcoming)
        $relatedEvents = $eventRepository->createQueryBuilder('e')
            ->andWhere('e.id != :currentId')
            ->andWhere('e.dateStart >= :now')
            ->setParameter('currentId', $event->getId())
            ->setParameter('now', new \DateTime())
            ->setMaxResults(3)
            ->orderBy('e.dateStart', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'userRegistration' => $userRegistration,
            'relatedEvents' => $relatedEvents,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        // Security: Must be owner or admin
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof \App\Entity\User || $event->getOrganizerId() !== $user->getId())) {
            throw $this->createAccessDeniedException('You can only edit your own events.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImageUrl('uploads/events/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        // Security: Must be owner or admin
        if (!$this->isGranted('ROLE_ADMIN') && (!$user instanceof \App\Entity\User || $event->getOrganizerId() !== $user->getId())) {
            throw $this->createAccessDeniedException('You can only delete your own events.');
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/generate-description', name: 'app_event_generate_ai', methods: ['POST'])]
    public function generateAiDescription(Request $request, HttpClientInterface $client): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';

        if (empty($title)) {
            return new JsonResponse(['error' => 'Title is required'], 400);
        }

        try {
            $prompt = "Write a captivating 2-3 sentence event description in English for: " . $title . ". Focus on engagement and professionalism.";
            $url = 'https://text.pollinations.ai/' . urlencode($prompt);
            
            $response = $client->request('GET', $url, [
                'timeout' => 10,
            ]);

            $content = $response->getContent();
            if ($response->getStatusCode() === 200 && !empty($content)) {
                return new JsonResponse(['description' => trim($content)]);
            }

            return new JsonResponse(['description' => $this->fallbackDescription($title)]);
        } catch (\Exception $e) {
            return new JsonResponse(['description' => $this->fallbackDescription($title), 'debug' => $e->getMessage()]);
        }
    }

    private function fallbackDescription(string $title): string
    {
        $templates = [
            "Join us for this exceptional event: '{title}'. It's a unique opportunity to explore new perspectives and share enriching moments together.",
            "Discover '{title}', an event designed to bring you concrete solutions and high-quality support. Don't miss this opportunity!",
            "We invite you to '{title}', a moment of sharing and learning at the heart of our program. An essential event for your personal and professional growth."
        ];
        
        $desc = $templates[array_rand($templates)];
        return str_replace('{title}', $title, $desc);
    }
}
