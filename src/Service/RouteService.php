<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RouteService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiKey = $_ENV['ORS_API_KEY'];
    }

    public function getRoute(array $start, array $end): array
    {
        $response = $this->client->request(
            'POST',
            'https://api.openrouteservice.org/v2/directions/driving-car',
            [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'coordinates' => [$start, $end]
                ]
            ]
        );

        $data = $response->toArray();

        if (!isset($data['routes'][0])) {
            return [
                'error' => 'Impossible de calculer l’itinéraire'
            ];
        }

        return [
            'distance' => $data['routes'][0]['summary']['distance'], // mètres
            'duration' => $data['routes'][0]['summary']['duration'], // secondes
            'geometry' => $data['routes'][0]['geometry']
        ];
    }
}
