<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\TherapistRepository;
use App\Service\GeminiAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AiCompanionController extends AbstractController
{
    #[Route('/ai-companion', name: 'app_ai_companion', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        TherapistRepository $therapistRepository,
        GeminiAIService $aiService
    ): Response {
        $responseMarkdown = null;
        $userMessage = $request->request->get('message');

        if ($request->isMethod('POST') && !empty($userMessage)) {
            // Fetch relevant data to feed the AI
            $products = $productRepository->findBy(['status' => 'available']);
            $therapists = $therapistRepository->findBy(['status' => 'ACTIVE']);

            // Simplify array to save tokens
            $productsData = array_map(function($p) {
                return [
                    'id' => $p->getId(),
                    'name' => $p->getName(),
                    'category' => $p->getCategory(),
                    'description' => $p->getDescription(),
                    'price' => $p->getPrice() . ' TND',
                    'photo_url' => $p->getPhotoUrl(),
                ];
            }, $products);

            $therapistsData = array_map(function($t) {
                return [
                    'id' => $t->getId(),
                    'name' => $t->getFirstName() . ' ' . $t->getLastName(),
                    'specialization' => $t->getSpecialization(),
                    'consultation_type' => $t->getConsultationType() ?? 'Any',
                    'photo_url' => $t->getPhotoUrl(),
                ];
            }, $therapists);

            // Call Gemini
            $responseMarkdown = $aiService->analyzeSymptomAndRecommend($userMessage, $productsData, $therapistsData);
        }

        return $this->render('ai_companion/index.html.twig', [
            'ai_response' => $responseMarkdown,
            'user_message' => $userMessage,
        ]);
    }
}
