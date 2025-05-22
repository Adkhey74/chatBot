<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;

class UserController extends AbstractController
{
  #[Route('/api/user/me', name: 'user', methods: ['GET'])]
  public function __invoke(Security $security): JsonResponse
  {
    $user = $security->getUser();

    if (!$user instanceof User) {
      throw new \LogicException('User must be an instance of User.');
    }


    return $this->json($user, 200, [], ['groups' => ['user:read']]);
  }
}
