<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\TherapistRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/appointments', name: 'api_appointments_')]
class AppointmentController extends AbstractController
{
    public function __construct(
        private AppointmentRepository $repository,
        private TherapistRepository $therapistRepo,
        private UserRepository $userRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findAll()));
    }

    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    public function upcoming(): JsonResponse
    {
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findUpcoming()));
    }

    #[Route('/therapist/{therapistId}', name: 'by_therapist', methods: ['GET'])]
    public function byTherapist(int $therapistId): JsonResponse
    {
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findByTherapist($therapistId)));
    }

    #[Route('/patient/{patientId}', name: 'by_patient', methods: ['GET'])]
    public function byPatient(int $patientId): JsonResponse
    {
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findByPatient($patientId)));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $a = $this->repository->find($id);
        if (!$a) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($a));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $therapist = $this->therapistRepo->find($data['therapist_id']);
        $patient   = $this->userRepo->find($data['patient_id']);
        if (!$therapist || !$patient) return $this->json(['error' => 'Therapist or patient not found'], Response::HTTP_BAD_REQUEST);

        $a = new Appointment();
        $a->setAppointmentDate(new \DateTime($data['appointment_date']));
        $a->setStartTime(new \DateTime($data['start_time']));
        $a->setEndTime(new \DateTime($data['end_time']));
        $a->setStatus($data['status'] ?? 'pending');
        $a->setType($data['type'] ?? null);
        $a->setTherapist($therapist);
        $a->setPatient($patient);
        $this->repository->save($a);
        return $this->json($this->serialize($a), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $a = $this->repository->find($id);
        if (!$a) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['appointment_date'])) $a->setAppointmentDate(new \DateTime($data['appointment_date']));
        if (isset($data['start_time']))       $a->setStartTime(new \DateTime($data['start_time']));
        if (isset($data['end_time']))         $a->setEndTime(new \DateTime($data['end_time']));
        if (isset($data['status']))           $a->setStatus($data['status']);
        if (isset($data['type']))             $a->setType($data['type']);
        $this->repository->save($a);
        return $this->json($this->serialize($a));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $a = $this->repository->find($id);
        if (!$a) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($a);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serialize(Appointment $a): array
    {
        return [
            'id'               => $a->getId(),
            'appointment_date' => $a->getAppointmentDate()->format('Y-m-d'),
            'start_time'       => $a->getStartTime()->format('H:i:s'),
            'end_time'         => $a->getEndTime()->format('H:i:s'),
            'status'           => $a->getStatus(),
            'type'             => $a->getType(),
            'therapist_id'     => $a->getTherapist()->getId(),
            'patient_id'       => $a->getPatient()->getId(),
            'created_at'       => $a->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
