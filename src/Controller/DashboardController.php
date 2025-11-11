<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $visitedClubs = $user->getStadiumsVisited()->map(function($club) {
            return [
                'id' => $club->getId(),
                'name' => $club->getNomClub(),
                'stadium' => $club->getNomStadeClub(),
                'latitude' => $club->getLatitudeStadeClub(),
                'longitude' => $club->getLongitudeStadeClub(),
                'logo' => $club->getLogoClub(),
            ];
        })->toArray();

        return $this->render('dashboard/index.html.twig', [
            'clubs' => $visitedClubs,
        ]);
    }
}
