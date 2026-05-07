<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AudioGeneratorService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $voiceRssApiKey
    ) {
    }

    public function textToSpeech(string $text): string
    {
        $text = mb_substr(strip_tags($text), 0, 4096);

        $response = $this->httpClient->request('POST', 'https://api.voicerss.org/', [
            'body' => [
                'key' => $this->voiceRssApiKey,
                'src' => $text,
                'hl' => 'en-us',
                'v' => 'Linda',
                'r' => '0',
                'c' => 'MP3',
                'f' => '44khz_16bit_stereo',
            ],
        ]);

        $content = $response->getContent();

        // VoiceRSS returns a plain text error message if something is wrong
        if (str_starts_with($content, 'ERROR:')) {
            throw new \RuntimeException('VoiceRSS error: ' . $content);
        }

        return $content;

        $response = $this->httpClient->request('POST', 'https://api.voicerss.org/', [
            'verify_peer' => false,
            'verify_host' => false,
            'body' => [
                'key' => $this->voiceRssApiKey,
                'src' => $text,
                'hl' => 'en-us',
                'v' => 'Linda',
                'r' => '0',
                'c' => 'MP3',
                'f' => '44khz_16bit_stereo',
            ],
        ]);
    }
}