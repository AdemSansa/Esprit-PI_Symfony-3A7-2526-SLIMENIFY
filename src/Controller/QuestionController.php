<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/questions', name: 'api_questions_')]
class QuestionController extends AbstractController
{
    public function __construct(private QuestionRepository $repository) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($q) => $this->serialize($q), $this->repository->findAll()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $q = $this->repository->find($id);
        if (!$q) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($q));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $q = new Question();
        $q->setQuestionText($data['question_text']);
        $q->setRequired($data['required'] ?? true);
        $q->setImagePath($data['image_path'] ?? '');
        $this->repository->save($q);
        return $this->json($this->serialize($q), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $q = $this->repository->find($id);
        if (!$q) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['question_text'])) $q->setQuestionText($data['question_text']);
        if (isset($data['required']))      $q->setRequired($data['required']);
        if (isset($data['image_path']))    $q->setImagePath($data['image_path']);
        $this->repository->save($q);
        return $this->json($this->serialize($q));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $q = $this->repository->find($id);
        if (!$q) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $this->repository->remove($q);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serialize(Question $q): array
    {
        return [
            'id'            => $q->getId(),
            'question_text' => $q->getQuestionText(),
            'required'      => $q->isRequired(),
            'image_path'    => $q->getImagePath(),
            'created_at'    => $q->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
