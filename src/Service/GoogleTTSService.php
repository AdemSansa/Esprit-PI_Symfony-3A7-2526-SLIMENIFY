<?php
// src/Service/GoogleTTSService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleTTSService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $googleApiKey)
    {
        $this->client = $client;
        $this->apiKey = $googleApiKey;
    }

    public function generateAudio($text)
    {
        $response = $this->client->request(
            'POST',
            'https://texttospeech.googleapis.com/v1/text:synthesize?key='.$this->apiKey,
            [
                'json' => [
                    'input' => ['text' => $text],
                    'voice' => [
                        'languageCode' => 'fr-FR',
                        'name' => 'fr-FR-Wavenet-D'
                    ],
                    'audioConfig' => [
                        'audioEncoding' => 'MP3'
                    ]
                ]
            ]
        );

        $data = $response->toArray();
        $audioContent = base64_decode($data['audioContent']);

        $fileName = 'audio_'.uniqid().'.mp3';
        file_put_contents('public/audio/'.$fileName, $audioContent);

        return $fileName;
    }
}