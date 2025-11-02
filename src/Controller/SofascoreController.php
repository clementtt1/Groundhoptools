<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SofascoreController extends AbstractController
{
   #[Route('/api/matchs', name: 'api_matchs')]
public function getMatchs(Request $request): JsonResponse
{
    $date = $request->query->get('date');
    if (!$date) {
        return new JsonResponse(['error' => 'missing_date'], 400);
    }

    $jsonFile = $this->getParameter('kernel.project_dir') . '/public/data/matchs.json';

    if (!file_exists($jsonFile)) {
        return new JsonResponse(['error' => 'file_not_found'], 404);
    }

    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);

    if (!is_array($data) || !isset($data['fixtures']) || !is_array($data['fixtures'])) {
        return new JsonResponse(['error' => 'invalid_json_file'], 500);
    }

    // On récupère bien le tableau des fixtures
    $matches = $data['fixtures'];

    // Filtrer uniquement les matchs de la date choisie
    $filtered = array_filter($matches, function ($m) use ($date) {
        return isset($m['date']) && $m['date'] === $date;
    });

    // Réindexer le tableau
    $filtered = array_values($filtered);

    return new JsonResponse(['events' => $filtered]);
}

}
