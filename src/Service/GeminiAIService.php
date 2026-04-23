<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeminiAIService
{
    private string $apiKey;
    private string $hfApiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        ?string $geminiApiKey = '',
        ?string $hfApiKey = ''
    ) {
        $this->apiKey = $geminiApiKey ?? '';
        $this->hfApiKey = $hfApiKey ?? '';
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
                return $this->useHuggingFaceFallback($prompt);
            }

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Could not generate summary.";

        } catch (\Exception $e) {
            $this->logger->error("Gemini AIService Exception: " . $e->getMessage());
            return $this->useHuggingFaceFallback($prompt);
        }
    }

    /**
     * Fallback to Hugging Face Inference API if Gemini fails.
     */
    private function useHuggingFaceFallback(string $prompt): string
    {
        $this->logger->info("Attempting Hugging Face fallback for AI summary...");
        
        try {
            $headers = ['Content-Type' => 'application/json'];
            if (!empty($this->hfApiKey)) {
                $headers['Authorization'] = 'Bearer ' . $this->hfApiKey;
            }

            // Using Mistral 7B Instruct as a solid text model
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2', [
                'headers' => $headers,
                'json' => [
                    'inputs' => "[INST] " . $prompt . " [/INST]",
                    'parameters' => [
                        'return_full_text' => false,
                        'max_new_tokens' => 300,
                        'temperature' => 0.7
                    ]
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorMsg = $response->getContent(false);
                $this->logger->error("Hugging Face API Fallback Error (Status $statusCode): " . $errorMsg);
                return "AI Summarization (Gemini & Fallback) is currently unavailable due to API limits or errors.";
            }

            $data = $response->toArray();
            if (isset($data[0]['generated_text'])) {
                return trim($data[0]['generated_text']);
            }

            return "Could not generate summary via fallback AI.";

        } catch (\Exception $e) {
            $this->logger->error("Hugging Face Fallback Exception: " . $e->getMessage());
            return "AI Summarization completely failed: " . $e->getMessage();
        }
    }
}
