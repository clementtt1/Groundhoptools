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
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!$start || !$end) {
            return new JsonResponse(['error' => 'missing_dates'], 400);
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

        $matches = $data['fixtures'];

        // ✅ Filtrer les matchs selon startTime
        $filtered = array_filter($matches, function ($m) use ($start, $end) {
            if (!isset($m['startTime'])) {
                return false;
            }

            try {
                $matchDateTime = new \DateTime($m['startTime']);
                $startDateTime = new \DateTime($start . ' 00:00:00');
                $endDateTime = new \DateTime($end . ' 23:59:59');
            } catch (\Exception $e) {
                return false;
            }

            return $matchDateTime >= $startDateTime && $matchDateTime <= $endDateTime;
        });

        return new JsonResponse(['events' => array_values($filtered)]);
    }
}

