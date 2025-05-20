<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;

class UserDriverController extends AbstractController
{
    #[Route('/api/users/{id}/drivers', name: 'user_drivers', methods: ['GET'])]
    public function __invoke(User $user): JsonResponse
    {
        return $this->json($user->getDrivers(), 200, [] , ['groups' => ['driver:read']]);
    }
}
