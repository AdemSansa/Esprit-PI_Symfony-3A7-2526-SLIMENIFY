<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reviews', name: 'api_reviews_')]
class ReviewController extends AbstractController
{
    public function __construct(
        private ReviewRepository $repository,
        private UserRepository $userRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($r) => $this->serialize($r), $this->repository->findAll()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $r = $this->repository->find($id);
        if (!$r) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($r));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepo->find($data['user_id']);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);

        $r = new Review();
        $r->setContent($data['content']);
        $r->setUser($user);
        $this->repository->save($r);
        return $this->json($this->serialize($r), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $r = $this->repository->find($id);
        if (!$r) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['content'])) $r->setContent($data['content']);
        $this->repository->save($r);
        return $this->json($this->serialize($r));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $r = $this->repository->find($id);
        if (!$r) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($r);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serialize(Review $r): array
    {
        return [
            'id'         => $r->getId(),
            'content'    => $r->getContent(),
            'user_id'    => $r->getUser()->getId(),
            'created_at' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
            'replies'    => $r->getReplies()->map(fn($reply) => [
                'id'           => $reply->getId(),
                'content'      => $reply->getContent(),
                'therapist_id' => $reply->getTherapist()?->getId(),
                'created_at'   => $reply->getCreatedAt()->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }
}
