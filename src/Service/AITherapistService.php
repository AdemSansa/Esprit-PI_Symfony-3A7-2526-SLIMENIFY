<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AITherapistService
{
    private Client $client;
    private string $baseUrl;

    public function __construct(string $aiTherapistUrl)
    {
        $this->baseUrl = $aiTherapistUrl;
        $this->client  = new Client([
            'base_uri' => $aiTherapistUrl,
            'timeout'  => 30,
        ]);
    }

    public function chat(string $patientId, string $message): array
    {
        try {
            $response = $this->client->post('/api/therapy/chat', [
                'json' => [
                    'patient_id' => $patientId,
                    'message'    => $message,
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            return [
                'reply'           => 'I am sorry, I am temporarily unavailable. 
                                      Please contact your therapist directly.',
                'crisis_detected' => false,
                'error'           => true,
            ];
        }
    }

    public function getHistory(string $patientId): array
    {
        try {
            $response = $this->client->get("/api/therapy/history/{$patientId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return ['conversation' => [], 'message_count' => 0];
        }
    }

    public function clearSession(string $patientId): void
    {
        try {
            $this->client->post('/api/therapy/clear', [
                'json' => ['patient_id' => $patientId]
            ]);
        } catch (GuzzleException $e) {
            // silent fail
        }
    }
}