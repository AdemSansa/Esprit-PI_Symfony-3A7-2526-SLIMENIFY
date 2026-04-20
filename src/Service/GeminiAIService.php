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
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $this->apiKey, [
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

    /**
     * Recommends products and therapists based on user symptoms.
     */
    public function analyzeSymptomAndRecommend(string $message, array $productsData, array $therapistsData): string
    {
        if (empty($this->apiKey)) {
            return "La clé d'API Gemini n'est pas configurée. Veuillez ajouter GEMINI_API_KEY à votre fichier .env.";
        }

        $productsJson = json_encode($productsData);
        $therapistsJson = json_encode($therapistsData);

        $prompt = <<<EOT
Tu es un assistant virtuel en psychologie clinique ultra-professionnel, raffiné et empathique. 
Un utilisateur a partagé son état d'esprit/ses symptômes avec toi.
Message de l'utilisateur : "$message"

Analyse sa situation avec élégance et une grande finesse psychologique.
Ensuite, suggère-lui quelques produits et thérapeutes de notre catalogue.
Formate ta réponse en Markdown impeccable. **Always respond in English, regardless of the user's input language.**

**STYLE RULES:**
- Be CONCISE. Avoid long paragraphs.
- For **PRODUCTS**: Format as a **clickable link** in Markdown: **[ ![name](photo_url) **Name** - Price ](/product/ID/show)**.
- For **THERAPISTS**: Format as **static content** (NOT a link): **![name](photo_url) **Name** - Specialty**.
- Provide a very short, impactful description for both.

Produits disponibles (format JSON):
$productsJson

Thérapeutes disponibles (format JSON):
$therapistsJson

Instructions :
1. A short, empathetic, and refined introduction (2-3 sentences max).
2. Section "For your journey": 2 products with Photo + Name + Price + a micro-sentence.
3. Section "Professional support": 1 or 2 therapists with Photo + Name + Specialty.
4. A very short and soothing closing message.
EOT;

        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $errorResponse = $response->getContent(false);
                $errorData = json_decode($errorResponse, true);
                $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue';
                
                $this->logger->error("Gemini API Error (Status $statusCode): " . $errorResponse);
                return "Désolé, il y a un problème de configuration avec l'IA : " . $errorMessage;
            }

            $data = $response->toArray();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas pu formuler de suggestion.";

        } catch (\Exception $e) {
            $this->logger->error("Gemini AIService Exception: " . $e->getMessage());
            return "Une exception est survenue : " . $e->getMessage();
        }
    }
}
