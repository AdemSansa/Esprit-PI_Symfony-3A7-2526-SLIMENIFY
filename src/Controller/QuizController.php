<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use App\Repository\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quizzes', name: 'api_quizzes_')]
class QuizController extends AbstractController
{
    public function __construct(
        private QuizRepository $repository,
        private QuestionRepository $questionRepo
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map(fn($q) => $this->serialize($q), $this->repository->findAll()));
    }

    #[Route('/active', name: 'active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        return $this->json(array_map(fn($q) => $this->serialize($q), $this->repository->findActive()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $q = $this->repository->find($id);
        if (!$q) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        return $this->json($this->serialize($q, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $q = new Quiz();
        $q->setTitle($data['title']);
        $q->setDescription($data['description'] ?? null);
        $q->setCategory($data['category'] ?? null);
        $q->setTotalQuestions($data['total_questions'] ?? 0);
        $q->setActive(isset($data['active']) ? (int) $data['active'] : Quiz::STATUS_UNDER_REVIEW);
        $q->setMinScore($data['min_score'] ?? 0);
        $q->setMaxScore($data['max_score'] ?? 0);

        if (!empty($data['question_ids'])) {
            foreach ($data['question_ids'] as $qid) {
                $question = $this->questionRepo->find($qid);
                if ($question) $q->addQuestion($question);
            }
        }
        $this->repository->save($q);
        return $this->json($this->serialize($q), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $q = $this->repository->find($id);
        if (!$q) return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);
        if (isset($data['title']))           $q->setTitle($data['title']);
        if (isset($data['description']))     $q->setDescription($data['description']);
        if (isset($data['category']))        $q->setCategory($data['category']);
        if (isset($data['total_questions'])) $q->setTotalQuestions($data['total_questions']);
        if (isset($data['active']))          $q->setActive((int) $data['active']);
        if (isset($data['min_score']))       $q->setMinScore($data['min_score']);
        if (isset($data['max_score']))       $q->setMaxScore($data['max_score']);
        $q->setUpdatedAt(new \DateTimeImmutable());
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

    /** @return array<string, mixed> */
    private function serialize(Quiz $q, bool $withQuestions = false): array
    {
        $data = [
            'id'              => $q->getId(),
            'title'           => $q->getTitle(),
            'description'     => $q->getDescription(),
            'category'        => $q->getCategory(),
            'total_questions' => $q->getTotalQuestions(),
            'active'          => $q->getActive(),
            'min_score'       => $q->getMinScore(),
            'max_score'       => $q->getMaxScore(),
            'created_at'      => $q->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        if ($withQuestions) {
            $data['questions'] = $q->getQuestions()->map(fn($question) => [
                'id'            => $question->getId(),
                'question_text' => $question->getQuestionText(),
                'required'      => $question->isRequired(),
                'image_path'    => $question->getImagePath(),
            ])->toArray();
        }

        return $data;
    }
}
