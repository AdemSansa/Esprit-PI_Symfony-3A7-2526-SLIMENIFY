<?php

namespace App\Controller;

use App\Service\AITherapistService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
#[Route('/therapyAI')]
class TherapyChatController extends AbstractController
{
    public function __construct(
        private AITherapistService $aiService
    ) {}

    #[Route('/chat', name: 'therapy_chat_page', methods: ['GET'])]
    public function chatPage(): Response
    {
        $patient = $this->getUser();

        return $this->render('ai_companion/chat.html.twig', [
            'patient_name' => $patient->getFirstName(),
            'patient_id'   => $patient->getId(),
        ]);
    }

    #[Route('/chat/send', name: 'therapy_chat_send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $patient = $this->getUser();
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Message cannot be empty'], 400);
        }

        $result = $this->aiService->chat(
            (string) $patient->getId(),
            $message
        );

        // If crisis detected — notify the therapist
        if ($result['crisis_detected'] ?? false) {
            // TODO: add notification to therapist here
            // e.g. send email or create alert in DB
        }

        return $this->json([
            'reply'           => $result['reply'],
            'crisis_detected' => $result['crisis_detected'] ?? false,
        ]);
    }

    #[Route('/chat/clear', name: 'therapy_chat_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $patient = $this->getUser();
        $this->aiService->clearSession((string) $patient->getId());
        return $this->json(['status' => 'cleared']);
    }
}