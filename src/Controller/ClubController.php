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

    #[Route('/api/clubs', name: 'api_clubs', methods: ['GET'])]
    public function getClubs(ClubRepository $clubRepo, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $search = strtolower($request->query->get('q', ''));

        $visited = $user ? $user->getStadiumsVisited()->map(fn($c) => $c->getId())->toArray() : [];

        $clubs = $clubRepo->createQueryBuilder('c')
            ->where('LOWER(c.nom_club) LIKE :q')
            ->andWhere('c.id NOT IN (:visited)')
            ->setParameter('q', '%' . $search . '%')
            ->setParameter('visited', count($visited) ? $visited : [0])
            ->orderBy('c.nom_club', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = array_map(fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getNomClub(),
        ], $clubs);

        return new JsonResponse($data);
    }


    #[Route('/api/add-visited', name: 'api_add_visited', methods: ['POST'])]
    public function addVisited(Request $request, ClubRepository $clubRepo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $clubId = $data['clubId'] ?? null;

        $club = $clubRepo->find($clubId);
        if (!$club) {
            return new JsonResponse(['error' => 'Club introuvable'], 404);
        }

        if (!$user->getStadiumsVisited()->contains($club)) {
            $user->addStadiumsVisited($club);
            $em->persist($user);
            $em->flush();
        }

        return new JsonResponse(['success' => true]);
    }
}