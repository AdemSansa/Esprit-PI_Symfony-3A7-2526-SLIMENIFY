<?php

namespace App\Controller;

use App\Entity\Availability;
use App\Repository\AvailabilityRepository;
use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/availabilities', name: 'api_availabilities_')]
class AvailabilityController extends AbstractController
{
    public function __construct(
        private AvailabilityRepository $repository,
        private TherapistRepository $therapistRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findAll()));
    }

    #[Route('/therapist/{id}', name: 'by_therapist', methods: ['GET'])]
    public function byTherapist(int $id): JsonResponse
    {
        $therapist = $this->therapistRepo->find($id);
        if (!$therapist) return $this->json(['error' => 'Therapist not found'], Response::HTTP_NOT_FOUND);
        return $this->json(array_map(fn($a) => $this->serialize($a), $this->repository->findBy(['therapist' => $therapist])));
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
        if (!$therapist) return $this->json(['error' => 'Therapist not found'], Response::HTTP_BAD_REQUEST);

        $a = new Availability();
        $a->setDay(strtoupper($data['day']));
        $a->setStartTime(new \DateTime($data['start_time']));
        $a->setEndTime(new \DateTime($data['end_time']));
        $a->setIsAvailable($data['is_available'] ?? true);
        $a->setSpecificDate(isset($data['specific_date']) ? new \DateTime($data['specific_date']) : null);
        $a->setTherapist($therapist);
        $this->repository->save($a);
        return $this->json($this->serialize($a), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $a = $this->repository->find($id);
        if (!$a) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['day']))           $a->setDay(strtoupper($data['day']));
        if (isset($data['start_time']))    $a->setStartTime(new \DateTime($data['start_time']));
        if (isset($data['end_time']))      $a->setEndTime(new \DateTime($data['end_time']));
        if (isset($data['is_available']))  $a->setIsAvailable($data['is_available']);
        if (array_key_exists('specific_date', $data)) {
            $a->setSpecificDate($data['specific_date'] ? new \DateTime($data['specific_date']) : null);
        }
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

    private function serialize(Availability $a): array
    {
        return [
            'id'            => $a->getId(),
            'day'           => $a->getDay(),
            'start_time'    => $a->getStartTime()->format('H:i:s'),
            'end_time'      => $a->getEndTime()->format('H:i:s'),
            'is_available'  => $a->isAvailable(),
            'specific_date' => $a->getSpecificDate()?->format('Y-m-d'),
            'therapist_id'  => $a->getTherapist()->getId(),
        ];
    }
}
