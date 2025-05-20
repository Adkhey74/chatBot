<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class UserDriverController extends AbstractController
{
    #[Route('/api/user/drivers', name: 'user_drivers', methods: ['GET'])]
    public function __invoke(Security $security): JsonResponse
    {
        $user = $security->getUser();
        return $this->json($user->getDrivers(), 200, [], ['groups' => ['driver:read']]);
    }
}
