<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClubController extends AbstractController
{
    #[Route('/', name: 'club_map')]
    public function index(ClubRepository $clubRepository): Response
        {
            $clubs = $clubRepository->findAll();

    // Transformer en tableau simple
    $clubsArray = array_map(function($club) {
        return [
            'name' => $club->getName(),
            'stadium' => $club->getStadium(),
            'latitude' => $club->getLatitude(),
            'longitude' => $club->getLongitude(),
            'logo' => '/images/logos/' . $club->getLogo(), // chemin relatif vers le logo
        ];
    }, $clubs);

    return $this->render('club/index.html.twig', [
        'clubs' => $clubsArray,
    ]);

    }
}