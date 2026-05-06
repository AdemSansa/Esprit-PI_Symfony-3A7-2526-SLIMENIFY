<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(private EventRepository $repository) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($e) => $this->serialize($e), $this->repository->findAll()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $e = $this->repository->find($id);
        if (!$e) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($e));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $e = new Event();
        $e->setTitle($data['title']);
        $e->setDescription($data['description'] ?? null);
        $e->setType($data['type'] ?? null);
        $e->setDateStart(new \DateTime($data['date_start']));
        $e->setDateEnd(isset($data['date_end']) ? new \DateTime($data['date_end']) : null);
        $e->setLocation($data['location'] ?? null);
        $e->setMaxParticipants($data['max_participants'] ?? null);
        $e->setStatus($data['status'] ?? 'draft');
        $e->setOrganizerId($data['organizer_id'] ?? null);
        $e->setImageUrl($data['image_url'] ?? '');
        $this->repository->save($e);
        return $this->json($this->serialize($e), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $e = $this->repository->find($id);
        if (!$e) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['title']))            $e->setTitle($data['title']);
        if (isset($data['description']))      $e->setDescription($data['description']);
        if (isset($data['type']))             $e->setType($data['type']);
        if (isset($data['date_start']))       $e->setDateStart(new \DateTime($data['date_start']));
        if (isset($data['date_end']))         $e->setDateEnd(new \DateTime($data['date_end']));
        if (isset($data['location']))         $e->setLocation($data['location']);
        if (isset($data['max_participants'])) $e->setMaxParticipants($data['max_participants']);
        if (isset($data['status']))           $e->setStatus($data['status']);
        if (isset($data['image_url']))        $e->setImageUrl($data['image_url']);
        $this->repository->save($e);
        return $this->json($this->serialize($e));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $e = $this->repository->find($id);
        if (!$e) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($e);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Event $e): array
    {
        return [
            'id'               => $e->getId(),
            'title'            => $e->getTitle(),
            'description'      => $e->getDescription(),
            'type'             => $e->getType(),
            'date_start'       => $e->getDateStart() ? $e->getDateStart()->format('Y-m-d H:i:s') : null,
            'date_end'         => $e->getDateEnd()?->format('Y-m-d H:i:s'),
            'location'         => $e->getLocation(),
            'max_participants' => $e->getMaxParticipants(),
            'status'           => $e->getStatus(),
            'organizer_id'     => $e->getOrganizerId(),
            'image_url'        => $e->getImageUrl(),
            'created_at'       => $e->getCreatedAt() ? $e->getCreatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
