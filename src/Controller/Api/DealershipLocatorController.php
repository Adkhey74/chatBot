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
        $user = $security->getUser();

        if (!$user || !method_exists($user, 'getAddress')) {
            return new JsonResponse(['error' => 'Utilisateur non connecté ou sans adresse'], 400);
        }

        $address = $user->getAddress();
        if (!$address) {
            return new JsonResponse(['error' => 'Adresse utilisateur manquante'], 400);
        }

        $coords = $this->geocodeAddress($address);
        if (!$coords) {
            return new JsonResponse(['error' => 'Adresse invalide ou introuvable'], 404);
        }

        $limit = max(1, (int)$request->query->get('limit', 10));
        $offset = max(0, (int)$request->query->get('offset', 0));

        $dealerships = $dealershipRepository->findNearest(
            $coords['lat'],
            $coords['lon'],
            $limit,
            $offset
        );

        $response = [];

        foreach ($dealerships as $dealership) {
            $availableSlots = [];

            for ($d = 0; $d < 7; $d++) {
                $date = (new \DateTime())->modify("+$d day");
                $daySlots = $this->generateTimeSlots($date);

                $dayStart = (clone $date)->setTime(0, 0, 0);
                $dayEnd = (clone $date)->setTime(23, 59, 59);

                $appointments = $appointmentRepository->createQueryBuilder('a')
                    ->andWhere('a.dealership = :dealership')
                    ->andWhere('a.appointmentDate BETWEEN :start AND :end')
                    ->setParameter('dealership', $dealership['id'])
                    ->setParameter('start', $dayStart)
                    ->setParameter('end', $dayEnd)
                    ->getQuery()
                    ->getResult();

                $taken = [];

                foreach ($appointments as $appointment) {
                    $start = clone $appointment->getAppointmentDate();
                    $timeUnit = $appointment->getCarOperation()?->getTimeUnit() ?? 1;
                    $durationInMinutes = $timeUnit * 60;

                    $end = (clone $start)->modify("+$durationInMinutes minutes");

                    while ($start < $end) {
                        $taken[] = $start->format('H:i');
                        $start->modify('+30 minutes');
                    }
                }

                $freeSlots = array_filter($daySlots, fn($slot) => !in_array($slot, $taken));
                $availableSlots[$date->format('Y-m-d')] = array_values($freeSlots);
            }

            $response[] = [
                'id' => $dealership['id'],
                'name' => $dealership['name'] ?? null,
                'availableSlots' => $availableSlots
            ];
        }

        return new JsonResponse($response);
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

    private function geocodeAddress(string $address): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        $opts = [
            "http" => [
                "header" => "User-Agent: MySymfonyApp/1.0\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        if (!empty($data[0])) {
            return [
                'lat' => (float)$data[0]['lat'],
                'lon' => (float)$data[0]['lon'],
            ];
        }

        return null;
    }
}
