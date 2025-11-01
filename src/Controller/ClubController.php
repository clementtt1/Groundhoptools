<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ClubRepository; 

final class ClubController extends AbstractController
{
    #[Route('/', name: 'club_map')]
    public function index(ClubRepository $clubRepository): Response
        {
            $clubs = $clubRepository->findAll();

    // Transformer en tableau simple
    $clubsArray = array_map(function($club) {
        return [
            'name' => $club->getNomClub(),
            'logo' => $club->getLogoClub(),
            'stadium' => $club->getNomStadeClub(),
            'latitude' => $club->getLatitudeStadeClub(),
            'longitude' => $club->getLongitudeStadeClub(),
        ];
    }, $clubs);

    return $this->render('club/index.html.twig', [
        'clubs' => $clubsArray,
    ]);

    }
}