<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $repository
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->repository->findAll();
        return $this->json(array_map(fn(User $u) => $this->serialize($u), $users));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->repository->find($id);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($user));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setFirstName($data['first_name']);
        $user->setEmail($data['email'] ?? null);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setRole($data['role'] ?? 'patient');
        $user->setLastName($data['last_name'] ?? null);
        $user->setPhone($data['phone'] ?? null);
        $user->setGender($data['gender'] ?? null);
        $user->setPhotoUrl($data['photo_url'] ?? null);
        $this->repository->save($user);
        return $this->json($this->serialize($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->repository->find($id);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['first_name'])) $user->setFirstName($data['first_name']);
        if (isset($data['email']))      $user->setEmail($data['email']);
        if (isset($data['password']))   $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        if (isset($data['role']))       $user->setRole($data['role']);
        if (isset($data['last_name']))  $user->setLastName($data['last_name']);
        if (isset($data['phone']))      $user->setPhone($data['phone']);
        if (isset($data['gender']))     $user->setGender($data['gender']);
        if (isset($data['photo_url']))  $user->setPhotoUrl($data['photo_url']);
        $this->repository->save($user);
        return $this->json($this->serialize($user));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->repository->find($id);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($user);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serialize(User $u): array
    {
        return [
            'id'             => $u->getId(),
            'first_name'     => $u->getFirstName(),
            'last_name'      => $u->getLastName(),
            'email'          => $u->getEmail(),
            'role'           => $u->getRole(),
            'phone'          => $u->getPhone(),
            'gender'         => $u->getGender(),
            'date_naissance' => $u->getDateNaissance()?->format('Y-m-d'),
            'photo_url'      => $u->getPhotoUrl(),
            'created_at'     => $u->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
