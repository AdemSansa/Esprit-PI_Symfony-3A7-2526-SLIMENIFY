<?php

namespace App\Controller;

use App\Service\GeminiAIService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai')]
class AiProductRecognitionController extends AbstractController
{
    #[Route('/recognize-product', name: 'app_api_ai_recognize_product', methods: ['POST'])]
    public function recognize(Request $request, GeminiAIService $aiService, ProductRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;

        if (!$base64Image) {
            return new JsonResponse(['error' => 'No image data provided'], 400);
        }

        // Clean up base64 prefix if present (e.g. data:image/jpeg;base64,...)
        if (str_contains($base64Image, ',')) {
            $base64Image = explode(',', $base64Image)[1];
        }

        // Fetch all product names to provide context to Gemini
        $products = $productRepository->findAll();
        $productNames = array_map(fn($p) => $p->getName(), $products);

        if (empty($productNames)) {
            return new JsonResponse(['error' => 'No products in catalog'], 404);
        }

        $recognizedName = $aiService->identifyProductFromImage($base64Image, $productNames);

        if ($recognizedName === 'ERROR') {
            return new JsonResponse(['error' => 'AI processing failed'], 500);
        }

        return new JsonResponse([
            'recognizedName' => $recognizedName,
            'found' => $recognizedName !== 'NOT_FOUND'
        ]);
    }
}
