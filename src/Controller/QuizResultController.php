<?php

namespace App\Controller;

use App\Entity\QuizResult;
use App\Repository\QuizResultRepository;
use App\Repository\QuizRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quiz-results', name: 'api_quiz_results_')]
class QuizResultController extends AbstractController
{
    public function __construct(
        private QuizResultRepository $repository,
        private QuizRepository $quizRepo,
        private UserRepository $userRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($r) => $this->serialize($r), $this->repository->findAll()));
    }

    #[Route('/user/{userId}', name: 'by_user', methods: ['GET'])]
    public function byUser(int $userId): JsonResponse
    {
        $user = $this->userRepo->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        return $this->json(array_map(fn($r) => $this->serialize($r), $this->repository->findBy(['user' => $user])));
    }

    #[Route('/quiz/{quizId}', name: 'by_quiz', methods: ['GET'])]
    public function byQuiz(int $quizId): JsonResponse
    {
        $quiz = $this->quizRepo->find($quizId);
        if (!$quiz) return $this->json(['error' => 'Quiz not found'], Response::HTTP_NOT_FOUND);
        return $this->json(array_map(fn($r) => $this->serialize($r), $this->repository->findBy(['quiz' => $quiz])));
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
        $user = $this->repository->getEntityManager()->getReference(\App\Entity\User::class, $data['user_id']);
        $quiz = $this->repository->getEntityManager()->getReference(\App\Entity\Quiz::class, $data['quiz_id']);

        $r = new QuizResult();
        $r->setUser($user);
        $r->setQuiz($quiz);
        $r->setScore($data['score']);
        $r->setResult($data['result']);
        $r->setMood($data['mood'] ?? null);
        $this->repository->save($r);
        return $this->json($this->serialize($r), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $r = $this->repository->find($id);
        if (!$r) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($r);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serialize(QuizResult $r): array
    {
        return [
            'id'       => $r->getId(),
            'user_id'  => $r->getUser()->getId(),
            'quiz_id'  => $r->getQuiz()->getId(),
            'score'    => $r->getScore(),
            'result'   => $r->getResult(),
            'mood'     => $r->getMood(),
            'taken_at' => $r->getTakenAt()->format('Y-m-d H:i:s'),
        ];
    }
}
