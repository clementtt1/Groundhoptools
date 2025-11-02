<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SofascoreController extends AbstractController
{
    #[Route('/api/sofascore', name: 'api_sofascore')]
    public function getMatches(Request $request, HttpClientInterface $client): JsonResponse
    {
        $date = $request->query->get('date');
        if (!$date) {
            return new JsonResponse(['error' => 'missing_date'], 400);
        }

        $url = "https://api.sofascore.com/api/v1/sport/football/scheduled-events/" . $date;

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; SymfonyApp/1.0)',
                ]
            ]);

            $data = $response->toArray(false);
            return new JsonResponse($data);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
}
