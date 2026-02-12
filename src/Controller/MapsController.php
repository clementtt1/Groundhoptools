<?php

namespace App\Controller;

use App\Service\RouteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MapsController extends AbstractController
{
    #[Route('/maps', name: 'app_maps')]
    public function index(): Response
    {
        return $this->render('maps/index.html.twig');
    }

    #[Route('/api/route', name: 'api_route', methods: ['GET'])]
    public function getRoute(Request $request, RouteService $routeService): JsonResponse
    {
        $startLat = $request->query->get('startLat');
        $startLng = $request->query->get('startLng');
        $endLat = $request->query->get('endLat');
        $endLng = $request->query->get('endLng');

        if (!$startLat || !$startLng || !$endLat || !$endLng) {
            return $this->json(['error' => 'Paramètres manquants'], 400);
        }

        $result = $routeService->getRoute(
            [(float)$startLng, (float)$startLat],
            [(float)$endLng, (float)$endLat]
        );

        return $this->json($result);
    }
}
