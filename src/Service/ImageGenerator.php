<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Service\CloudinaryUploader;


class ImageGenerator
{
    /** @see https://huggingface.co/models?inference_provider=hf-inference&pipeline_tag=text-to-image */
    private const DEFAULT_MODEL = 'black-forest-labs/FLUX.1-schnell';

    private const HF_INFERENCE_BASE = 'https://router.huggingface.co/hf-inference';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $projectDir,
        private CloudinaryUploader $cloudinaryUploader,
        private string $hfToken = '',
        private string $hfModel = self::DEFAULT_MODEL,
    ) {
    }

    public function generate(string $text): ?string
    {
        if ($this->hfToken === '') {
            $this->logger->warning('ImageGenerator: HF_TOKEN is not set; cannot generate images.');

            return null;
        }

        $prompt = $this->buildPrompt($text);
        $url = self::HF_INFERENCE_BASE.'/models/'.$this->encodeModelIdForPath($this->hfModel);

        try {
            $binary = $this->requestImage($url, $prompt);
            if ($binary === null) {
                return null;
            }

            // Upload to Cloudinary so the image persists in production
            try {
                return $this->cloudinaryUploader->uploadFromBinary($binary, 'slimenify/blogs');
            } catch (\Throwable $cldEx) {
                $this->logger->error('ImageGenerator: Cloudinary upload failed: '.$cldEx->getMessage());
                // Fallback: save locally (works only in dev)
                $filename = 'generated_'.time().'_'.bin2hex(random_bytes(4)).'.png';
                $dir = $this->projectDir.'/public/uploads/blogs';
                if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $this->logger->error('ImageGenerator: cannot create directory '.$dir);
                    return null;
                }
                $path = $dir.'/'.$filename;
                if (file_put_contents($path, $binary) === false) {
                    $this->logger->error('ImageGenerator: failed to write '.$path);
                    return null;
                }
                return '/uploads/blogs/'.$filename;
            }
        } catch (\Throwable $e) {
            $this->logger->error('ImageGenerator: '.$e->getMessage());
            return null;
        }
    }

    private function buildPrompt(string $text): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if (mb_strlen($clean) > 400) {
            $clean = mb_substr($clean, 0, 400).'…';
        }
        if ($clean === '') {
            $clean = 'wellness and mental health';
        }

        return 'Professional editorial blog header illustration, psychology and wellness theme, soft calming colors, abstract or symbolic, high quality, no text, no letters, no watermark: '.$clean;
    }

    /**
     * Encode each path segment of a Hub model id (org/name); do not encode "/" as %2F or the router returns 404.
     */
    private function encodeModelIdForPath(string $modelId): string
    {
        $segments = explode('/', $modelId);

        return implode('/', array_map(rawurlencode(...), $segments));
    }

    /**
     * @return string|null raw image bytes
     */
    private function requestImage(string $url, string $prompt): ?string
    {
        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->hfToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inputs' => $prompt,
            ],
            'timeout' => 180,
        ]);

        $binary = $this->extractImageFromResponse($response);
        if ($binary !== null) {
            return $binary;
        }

        if (503 === $response->getStatusCode()) {
            sleep(3);
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->hfToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                ],
                'timeout' => 180,
            ]);

            return $this->extractImageFromResponse($response);
        }

        $body = $response->getContent(false);
        $this->logger->error(sprintf(
            'ImageGenerator: HF API HTTP %s: %s',
            $response->getStatusCode(),
            mb_substr($body, 0, 800)
        ));

        return null;
    }

    private function extractImageFromResponse(ResponseInterface $response): ?string
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return null;
        }

        $contentType = strtolower($response->getHeaders(false)['content-type'][0] ?? '');

        if (str_contains($contentType, 'application/json')) {
            try {
                $data = $response->toArray(false);
            } catch (\Throwable) {
                return null;
            }

            if (isset($data['data'][0]['b64_json']) && is_string($data['data'][0]['b64_json'])) {
                $raw = base64_decode($data['data'][0]['b64_json'], true);

                return false !== $raw ? $raw : null;
            }

            if (isset($data['output'][0]) && is_string($data['output'][0]) && str_starts_with($data['output'][0], 'http')) {
                $img = $this->httpClient->request('GET', $data['output'][0], ['timeout' => 120]);

                return 200 === $img->getStatusCode() ? $img->getContent() : null;
            }

            $err = $data['error'] ?? $data['detail'] ?? $data['message'] ?? json_encode($data);
            $this->logger->error('ImageGenerator: HF JSON response: '.mb_substr((string) $err, 0, 600));

            return null;
        }

        $body = $response->getContent(false);
        if ($body === '' || str_starts_with(ltrim($body), '{')) {
            $this->logger->error('ImageGenerator: unexpected JSON or empty body from HF');

            return null;
        }

        return $body;
    }
}
