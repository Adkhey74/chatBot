<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class VehicleNotDriversController extends AbstractController
{
    #[Route('/api/vehicle/{id}/not-drivers', name: 'vehicle_not_drivers', methods: ['GET'])]
    public function __invoke(Vehicle $vehicle, Security $security): JsonResponse
    {
        $user = $security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of User.');
        }

        if (!$user->getVehicles()->contains($vehicle)) {
            throw new \LogicException('User is not owner of this vehicle.');
        }

        $collection1 = $user->getDrivers();
        $collection2 = $vehicle->getDrivers();

        $notDrivers = $collection1->filter(function ($driver) use ($collection2) {
            return !$collection2->exists(function ($key, $element) use ($driver) {
                return $element->getId() === $driver->getId();
            });
        });

        return $this->json(array_values($notDrivers->toArray()), 200, [], ['groups' => ['driver:read']]);
    }
}
