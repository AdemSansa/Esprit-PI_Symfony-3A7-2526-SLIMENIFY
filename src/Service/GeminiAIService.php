<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiAIService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $geminiApiKey = ''
    ) {
        $this->apiKey = $geminiApiKey;
    }

    /**
     * Summarizes appointment notes.
     * 
     * @param string[] $notes Array of note contents
     * @return string The summary or an error message
     */
    public function summarizeNotes(array $notes): string
    {
        if (empty($notes)) {
            return "No notes provided to summarize.";
        }

        if (empty($this->apiKey)) {
            return "Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.";
        }

        $combinedNotes = implode("\n---\n", $notes);
        $prompt = "As a clinical psychology assistant, summarize the following therapist notes from an appointment. Focus on the main themes, patient progress, and any critical observations. Keep it professional and concise (max 200 words).\n\nNotes:\n" . $combinedNotes;

        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error("Gemini API Error (Status $statusCode): " . $response->getContent(false));
                return "AI Summarization is currently unavailable (API Error).";
            }

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Could not generate summary.";

        } catch (\Exception $e) {
            $this->logger->error("Gemini AIService Exception: " . $e->getMessage());
            return "AI Summarization failed: " . $e->getMessage();
        }
    }
}
