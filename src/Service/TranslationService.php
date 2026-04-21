<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    
    private const MAX_QUERY_CHARS = 450;

    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function translate(string $text, string $from, string $to): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= self::MAX_QUERY_CHARS) {
            return $this->translateChunk($text, $from, $to);
        }

        $chunks = $this->splitIntoChunks($text, self::MAX_QUERY_CHARS);
        $parts = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $parts[] = $this->translateChunk($chunk, $from, $to);
        }

        return implode("\n\n", $parts);
    }

    private function translateChunk(string $text, string $from, string $to): string
    {
        if ($text === '') {
            return '';
        }

        $response = $this->client->request('GET', 'https://api.mymemory.translated.net/get', [
            'query' => [
                'q' => $text,
                'langpair' => $from.'|'.$to,
            ],
            'timeout' => 20,
        ]);

        $data = $response->toArray();

        return $data['responseData']['translatedText'] ?? $text;
    }

    /**
     * @return list<string>
     */
    private function splitIntoChunks(string $text, int $maxLen): array
    {
        $chunks = [];
        $remaining = $text;

        while (mb_strlen($remaining) > $maxLen) {
            $slice = mb_substr($remaining, 0, $maxLen);
            $cut = $maxLen;

            $nl = mb_strrpos($slice, "\n");
            if ($nl !== false && $nl > (int) ($maxLen * 0.4)) {
                $cut = $nl + 1;
            } else {
                $sp = mb_strrpos($slice, ' ');
                if ($sp !== false && $sp > (int) ($maxLen * 0.25)) {
                    $cut = $sp + 1;
                }
            }

            $chunks[] = mb_substr($remaining, 0, $cut);
            $remaining = mb_substr($remaining, $cut);
            $remaining = ltrim($remaining);
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }
}
