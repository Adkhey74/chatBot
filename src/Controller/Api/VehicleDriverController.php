<?php

namespace App\Controller\Api;

use App\Entity\Vehicle;
use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VehicleDriverController extends AbstractController
{
    #[Route('/api/vehicle/{id}/drivers', name: 'vehicle_drivers', methods: ['GET'])]
    public function getDrivers(Vehicle $vehicle): JsonResponse
    {
        return $this->json($vehicle->getDrivers(), 200, [], ['groups' => ['driver:read']]);
    }

    #[Route('/api/vehicle/{vehicleId}/driver/{driverId}', name: 'remove_vehicle_driver', methods: ['DELETE'])]
    public function removeDriver(int $vehicleId, int $driverId, EntityManagerInterface $entityManager): JsonResponse
    {
        $vehicle = $entityManager->getRepository(Vehicle::class)->find($vehicleId);
        $driver = $entityManager->getRepository(Driver::class)->find($driverId);

        if (!$vehicle || !$driver) {
            return $this->json(['message' => 'Vehicle or Driver not found'], 404);
        }

        $vehicle->removeDriver($driver);
        $entityManager->flush();

        return $this->json(['message' => 'Driver removed from vehicle successfully'], 200);
    }

    #[Route('/api/vehicle/{vehicleId}', name: 'update_vehicle_driver', methods: ['PATCH'])]
    public function updateDrivers(int $vehicleId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $vehicle = $entityManager->getRepository(Vehicle::class)->find($vehicleId);

            if (!$vehicle) {
                return $this->json(['message' => 'Vehicle not found'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['drivers']) || !is_array($data['drivers'])) {
                return $this->json(['message' => 'Invalid request format. Drivers array is required'], 400);
            }

            // Clear existing drivers
            foreach ($vehicle->getDrivers() as $driver) {
                $vehicle->removeDriver($driver);
            }

            // Add new drivers
            foreach ($data['drivers'] as $driverId) {
                $driver = $entityManager->getRepository(Driver::class)->find($driverId);

                if (!$driver) {
                    return $this->json(['message' => "Driver with ID $driverId not found"], 404);
                }

                $vehicle->addDriver($driver);
            }

            $entityManager->flush();

            return $this->json([
                'message' => 'Vehicle drivers updated successfully',
                'drivers' => $vehicle->getDrivers()
            ], 200, [], ['groups' => ['driver:read']]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred while updating vehicle drivers'], 500);
        }
    }
}
