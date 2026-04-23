<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class QuestionTransformerService
{
    private HttpClientInterface $client;
    // Calling the Render endpoint directly (no API key required)
    private const API_URL = 'https://question-transformer.onrender.com/transform';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function transformQuestion(string $question): array
    {
        $response = $this->client->request('POST', self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'question' => $question,
            ],
            'timeout' => 45, // Render free tier can take a moment to wake up
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception("Render API returned status {$statusCode}: " . $response->getContent(false));
        }

        $rawContent = trim($response->getContent());

        // Decode the JSON payload from the Render server
        $result = json_decode($rawContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("API returned an unexpected format. JSON error: " . json_last_error_msg());
        }

        // The Render API returns {"original": "...", "rewritten": {"simpler": "...", "reverse_meaning": "..."}}
        $rewritten = $result['rewritten'] ?? $result;

        /* 
         * Align the response keys to match what the frontend expects 
         * (simpler, reverse_meaning, more_emotional, more_abstract, softer_tone)
         * just in case the Render API returns slightly different keys.
         */
        $expectedKeys = ['simpler', 'reverse_meaning', 'more_emotional', 'more_abstract', 'softer_tone'];
        $formattedResult = [];
        $i = 0;
        
        foreach ($rewritten as $k => $v) {
            if (in_array($k, $expectedKeys)) {
                $formattedResult[$k] = $v;
            } else {
                // Safely map keys if they differ slightly
                $targetKey = $expectedKeys[$i] ?? $k;
                $formattedResult[$targetKey] = $v;
            }
            $i++;
        }

        return $formattedResult;
    }
}
