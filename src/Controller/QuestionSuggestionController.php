<?php

namespace App\Controller;

use App\Service\QuestionTransformerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class QuestionSuggestionController extends AbstractController
{
    #[Route('/api/ai-suggest', name: 'api_ai_suggest', methods: ['POST'])]
    public function suggest(Request $request, QuestionTransformerService $transformer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $questionText = $data['question'] ?? '';

        if (empty(trim($questionText))) {
            return new JsonResponse(['error' => 'Question text cannot be empty. Please type a question first.'], 400);
        }

        try {
            $suggestions = $transformer->transformQuestion($questionText);
            return new JsonResponse($suggestions);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
