<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserVehicleController extends AbstractController
{
    #[Route('/api/user/vehicles', name: 'user_vehicles', methods: ['GET'])]
    public function __invoke(Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of User.');
        }

        return $this->json($user->getVehicles(), 200, [], ['groups' => ['vehicle:read']]);
    }
}
