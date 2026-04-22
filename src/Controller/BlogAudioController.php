<?php

namespace App\Controller;

use App\Repository\BlogRepository;
use App\Service\AudioGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BlogAudioController extends AbstractController
{
    #[Route('/blog/{id}/audio', name: 'blog_audio')]
    public function generateAudio(
        int $id,
        BlogRepository $blogRepository,
        AudioGeneratorService $audioService
    ): JsonResponse {

        $blog = $blogRepository->find($id);

        if (!$blog) {
            return new JsonResponse(['error' => 'Blog not found'], 404);
        }

        $text = strip_tags($blog->getContent()); // important: clean HTML
        
        try {
            $fileName = $audioService->textToSpeech($text);

            return new JsonResponse([
                'message' => 'Audio generated successfully',
                'audio_url' => '/audios/' . $fileName
            ]);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'quota') ? 402 : 500;
            return new JsonResponse(['error' => $e->getMessage()], $status);
        }
    }
}