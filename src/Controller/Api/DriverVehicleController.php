<?php

namespace App\Controller\Api;

use App\Entity\Driver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DriverVehicleController extends AbstractController
{
    #[Route('/api/drivers/{id}/vehicles', name: 'driver_vehicles', methods: ['GET'])]
    public function __invoke(Driver $driver): JsonResponse
    {
        return $this->json($driver->getVehicles(), 200, [], ['groups' => ['vehicle:read']]);
    }
}
