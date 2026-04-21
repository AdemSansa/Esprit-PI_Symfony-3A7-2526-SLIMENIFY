<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiAnalysisService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $openAiApiKey
    ) {
        $this->apiKey = $openAiApiKey;
    }

    /**
     * Analyzes a patient message for psychological distress.
     * Returns ['level' => 'low|medium|high|critical', 'analysis' => 'text or null']
     */
    public function analyzeMessage(string $content): array
    {
        $default = ['level' => 'low', 'analysis' => null];

        if (empty(trim($content))) {
            return $default;
        }

        $prompt = <<<PROMPT
Tu es un assistant clinique spécialisé en santé mentale. Analyse le message suivant envoyé par un patient à son thérapeute.
Retourne UNIQUEMENT un objet JSON valide (sans markdown, sans backticks) avec exactement ces deux champs :
- "level": une de ces valeurs uniquement -> "low", "medium", "high", "critical"
- "analysis": une courte phrase d'explication en français (maximum 20 mots). Si level est "low", mets null.

Règles de classification :
- low = message normal, aucune détresse détectée
- medium = légère anxiété, tristesse, stress
- high = détresse importante, sentiment de désespoir, isolement fort
- critical = mention d'idées suicidaires, d'automutilation ou de crise grave -> alerte immédiate

Message du patient : "{$content}"
PROMPT;

        // --- DEBUT SIMULATION ---
        // Comme le compte OpenAI a une erreur 429 (Pas de crédit), nous simulons l'IA
        $contentLower = strtolower($content);
        
        if (str_contains($contentLower, 'finir avec la vie') || str_contains($contentLower, 'mourir') || str_contains($contentLower, 'suicide')) {
            return [
                'level' => 'critical',
                'analysis' => 'Risque suicidaire imminent détecté.'
            ];
        }
        
        if (str_contains($contentLower, 'stress') || str_contains($contentLower, 'anxieux') || str_contains($contentLower, 'peur')) {
            return [
                'level' => 'medium',
                'analysis' => 'Le patient exprime des signes d\'anxiété.'
            ];
        }
        
        if (str_contains($contentLower, 'triste') || str_contains($contentLower, 'déprimé') || str_contains($contentLower, 'lourd')) {
            return [
                'level' => 'high',
                'analysis' => 'Détresse émotionnelle significative.'
            ];
        }

        return ['level' => 'low', 'analysis' => null];
        // --- FIN SIMULATION ---
        
        /* Code API original commenté temporairement
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'      => 'gpt-4o-mini',
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 100,
                    'temperature' => 0.2,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $raw  = $data['choices'][0]['message']['content'] ?? '';
            $raw  = trim($raw);

            $parsed = json_decode($raw, true);

            if (!is_array($parsed) || !isset($parsed['level'])) {
                $this->logger->warning('AiAnalysisService: invalid JSON response', ['raw' => $raw]);
                return $default;
            }

            $allowedLevels = ['low', 'medium', 'high', 'critical'];
            $level = in_array($parsed['level'], $allowedLevels) ? $parsed['level'] : 'low';
            $analysis = $parsed['analysis'] ?? null;

            return ['level' => $level, 'analysis' => $analysis];

        } catch (\Throwable $e) {
            $this->logger->error('AiAnalysisService error: ' . $e->getMessage());
            return $default;
        }
        */
    }
}
