<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserVehicleController extends AbstractController
{
    #[Route('/api/users/{id}/vehicles', name: 'user_vehicles', methods: ['GET'])]
    public function __invoke(User $user): JsonResponse
    {
        return $this->json($user->getVehicles(), 200, [], ['groups' => ['vehicle:read']]);
    }
}
