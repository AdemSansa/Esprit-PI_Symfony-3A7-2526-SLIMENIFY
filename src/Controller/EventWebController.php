<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
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

#[Route('/events')]
class EventWebController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $query = $request->query->get('q', '');
        
        // Simple search
        if ($query) {
            $events = $eventRepository->createQueryBuilder('e')
                ->where('e.title LIKE :query OR e.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('e.dateStart', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $events = $eventRepository->findBy([], ['dateStart' => 'ASC']);
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'searchQuery' => $query,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
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

            $user = $this->getUser();
            if ($user && method_exists($user, 'getId')) {
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

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
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
            return new JsonResponse(['error' => 'Veuillez saisir un titre.'], 400);
        }

        try {
            // Simplest, most direct Pollinations call
            $prompt = "Rédige une description captivante en français pour cet événement : " . $title;
            // Shorter prompt to avoid URL length issues
            $url = 'https://text.pollinations.ai/' . urlencode($prompt);
            
            $response = $client->request('GET', $url, [
                'timeout' => 15, // Faster timeout to trigger fallback on lag
            ]);

            if ($response->getStatusCode() === 200 && !empty($text = trim($response->getContent()))) {
                return new JsonResponse(['description' => $text]);
            }
            
            // If status is not 200 or content is empty, use fallback
            return new JsonResponse(['description' => $this->fallbackDescription($title)]);
        } catch (\Exception $e) {
            // Failsafe: return a generated description even if API fails
            return new JsonResponse(['description' => $this->fallbackDescription($title)]);
        }
    }

    private function fallbackDescription(string $title): string
    {
        $templates = [
            "Rejoignez-nous pour cet événement exceptionnel : '{title}'. Une occasion unique d'explorer de nouvelles perspectives et de partager des moments enrichissants.",
            "Découvrez '{title}', un événement conçu pour vous apporter des solutions concrètes et un accompagnement de qualité. Ne manquez pas cette opportunité !",
            "Nous vous invitons à '{title}', un moment de partage et d'apprentissage au cœur de notre programme. Un événement incontournable pour votre développement."
        ];
        
        $desc = $templates[array_rand($templates)];
        return str_replace('{title}', $title, $desc);
    }
}
