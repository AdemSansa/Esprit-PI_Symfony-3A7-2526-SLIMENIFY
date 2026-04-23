<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeolocationService
{
    private const API_URL = 'http://ip-api.com/json/';

    public function __construct(
        private HttpClientInterface $client
    ) {}

    public function getLocation(string $ip): ?string
    {
        // Skip local IPs
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local Session';
        }

        try {
            $response = $this->client->request('GET', self::API_URL . $ip);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $city = $data['city'] ?? null;
                $country = $data['country'] ?? null;

                if ($city && $country) {
                    return $city . ', ' . $country;
                }
            }
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

        return 'Unknown Location';
    }
}
