<?php

namespace App\Controller;

use App\Entity\Note;
use App\Repository\NoteRepository;
use App\Repository\AppointmentRepository;
use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notes', name: 'api_notes_')]
class NoteController extends AbstractController
{
    public function __construct(
        private NoteRepository $repository,
        private AppointmentRepository $appointmentRepo,
        private TherapistRepository $therapistRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($n) => $this->serialize($n), $this->repository->findAll()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $n = $this->repository->find($id);
        if (!$n) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($n));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $appointment = $this->appointmentRepo->find($data['appointment_id']);
        $therapist   = $this->therapistRepo->find($data['therapist_id']);
        if (!$appointment || !$therapist) return $this->json(['error' => 'Invalid appointment or therapist'], Response::HTTP_BAD_REQUEST);

        $n = new Note();
        $n->setContent($data['content']);
        $n->setMood($data['mood'] ?? null);
        $n->setAppointment($appointment);
        $n->setTherapist($therapist);
        $this->repository->save($n);
        return $this->json($this->serialize($n), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $n = $this->repository->find($id);
        if (!$n) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['content'])) $n->setContent($data['content']);
        if (isset($data['mood']))    $n->setMood($data['mood']);
        $this->repository->save($n);
        return $this->json($this->serialize($n));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $n = $this->repository->find($id);
        if (!$n) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($n);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serialize(Note $n): array
    {
        return [
            'id'             => $n->getId(),
            'content'        => $n->getContent(),
            'mood'           => $n->getMood(),
            'appointment_id' => $n->getAppointment()->getId(),
            'therapist_id'   => $n->getTherapist()->getId(),
            'created_at'     => $n->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
