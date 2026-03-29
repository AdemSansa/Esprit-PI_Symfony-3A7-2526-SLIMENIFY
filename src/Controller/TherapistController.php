<?php

namespace App\Controller;

use App\Entity\Therapist;
use App\Repository\TherapistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/therapists', name: 'api_therapists_')]
class TherapistController extends AbstractController
{
    public function __construct(private TherapistRepository $repository) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($t) => $this->serialize($t), $this->repository->findAll()));
    }

    #[Route('/active', name: 'active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        return $this->json(array_map(fn($t) => $this->serialize($t), $this->repository->findActive()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $t = $this->repository->find($id);
        if (!$t) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($t));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $t = new Therapist();
        $t->setFirstName($data['first_name']);
        $t->setLastName($data['last_name']);
        $t->setEmail($data['email']);
        $t->setPhoneNumber($data['phone_number']);
        $t->setSpecialization($data['specialization']);
        $t->setDescription($data['description'] ?? null);
        $t->setConsultationType($data['consultation_type'] ?? null);
        $t->setStatus($data['status'] ?? 'ACTIVE');
        $t->setPhotoUrl($data['photo_url'] ?? '');
        $t->setDiplomaPath($data['diploma_path'] ?? '');
        $t->setLatitude($data['latitude'] ?? null);
        $t->setLongitude($data['longitude'] ?? null);
        if (isset($data['password'])) $t->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $this->repository->save($t);
        return $this->json($this->serialize($t), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $t = $this->repository->find($id);
        if (!$t) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['first_name']))       $t->setFirstName($data['first_name']);
        if (isset($data['last_name']))        $t->setLastName($data['last_name']);
        if (isset($data['email']))            $t->setEmail($data['email']);
        if (isset($data['phone_number']))     $t->setPhoneNumber($data['phone_number']);
        if (isset($data['specialization']))   $t->setSpecialization($data['specialization']);
        if (isset($data['description']))      $t->setDescription($data['description']);
        if (isset($data['consultation_type'])) $t->setConsultationType($data['consultation_type']);
        if (isset($data['status']))           $t->setStatus($data['status']);
        if (isset($data['photo_url']))        $t->setPhotoUrl($data['photo_url']);
        if (isset($data['latitude']))         $t->setLatitude($data['latitude']);
        if (isset($data['longitude']))        $t->setLongitude($data['longitude']);
        $t->setUpdatedAt(new \DateTimeImmutable());
        $this->repository->save($t);
        return $this->json($this->serialize($t));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $t = $this->repository->find($id);
        if (!$t) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($t);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serialize(Therapist $t): array
    {
        return [
            'id'                => $t->getId(),
            'first_name'        => $t->getFirstName(),
            'last_name'         => $t->getLastName(),
            'email'             => $t->getEmail(),
            'phone_number'      => $t->getPhoneNumber(),
            'specialization'    => $t->getSpecialization(),
            'description'       => $t->getDescription(),
            'consultation_type' => $t->getConsultationType(),
            'status'            => $t->getStatus(),
            'photo_url'         => $t->getPhotoUrl(),
            'latitude'          => $t->getLatitude(),
            'longitude'         => $t->getLongitude(),
            'created_at'        => $t->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
