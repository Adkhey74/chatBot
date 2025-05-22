<?php

namespace App\Controller\Api;

use App\Repository\DealershipRepository;
use App\Repository\AppointmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DealershipLocatorController extends AbstractController
{
    #[Route('/api/nearby-dealerships', name: 'get_nearby_dealerships', methods: ['GET'])]
    public function __invoke(
        Request $request,
        DealershipRepository $dealershipRepository,
        AppointmentRepository $appointmentRepository,
        Security $security
    ): JsonResponse {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!$latitude || !$longitude) {
            return new JsonResponse(['error' => 'Coordonnées géographiques manquantes'], 400);
        }

        $limit = max(1, (int)$request->query->get('limit', 10));
        $offset = max(0, (int)$request->query->get('offset', 0));

        $dealerships = $dealershipRepository->findNearest(
            (float)$latitude,
            (float)$longitude,
            $limit,
            $offset
        );

        $response = [];

        foreach ($dealerships as $dealership) {
            $dealershipData = [
                'id' => $dealership['id'],
                'name' => $dealership['name'] ?? null,
                'longitude' => $dealership['longitude'] ?? null,
                'latitude' => $dealership['latitude'] ?? null,
                'address' => $dealership['address'] ?? null,
                'zipCode' => $dealership['zipCode'] ?? null,
                'city' => $dealership['city'] ?? null,
            ];

            $response[] = $dealershipData;
        }

        return new JsonResponse([
            'dealerships' => $response,
            'userLocation' => [
                'latitude' => (float)$latitude,
                'longitude' => (float)$longitude,
            ]
        ]);
    }

    private function generateTimeSlots(\DateTime $date): array
    {
        $slots = [];

        // 9h à 12h
        $start = clone $date;
        $start->setTime(9, 0);
        $end = clone $date;
        $end->setTime(12, 0);

        while ($start < $end) {
            $slots[] = $start->format('H:i');
            $start->modify('+30 minutes');
        }

        // 14h à 18h
        $start->setTime(14, 0);
        $end->setTime(18, 0);

        while ($start < $end) {
            $slots[] = $start->format('H:i');
            $start->modify('+30 minutes');
        }

        return $slots;
    }
}
