<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\TeamHierarchyService;

class TeamHierarchyController extends AbstractController
{
    private $teamHierarchyService;

    public function __construct(TeamHierarchyService $teamHierarchyService)
    {
        $this->teamHierarchyService = $teamHierarchyService;
    }

    #[Route('/api/team-hierarchy', name: 'team_hierarchy', methods: ['POST'])]
    public function processTeamHierarchy(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        $filterQuery = $request->query->get('_q');

        try {
            $hierarchy = $this->teamHierarchyService->processCSV($file, $filterQuery);
            return new JsonResponse($hierarchy);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}