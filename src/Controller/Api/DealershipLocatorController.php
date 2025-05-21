<?php

namespace App\Controller\Api;

use App\Repository\DealershipRepository;
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
        Security $security
    ): JsonResponse {
        $user = $security->getUser();

        if (!$user || !method_exists($user, 'getAddress')) {
            return new JsonResponse(['error' => 'Utilisateur non connecté ou sans adresse'], 400);
        }
        /** @var \App\Entity\User $user */
        $address = $user->getAddress();
        if (!$address) {
            return new JsonResponse(['error' => 'Adresse utilisateur manquante'], 400);
        }

        $coords = $this->geocodeAddress($address);
        if (!$coords) {
            return new JsonResponse(['error' => 'Adresse invalide ou introuvable'], 404);
        }

        // 📦 Pagination
        $limit = max(1, (int)$request->query->get('limit', 10));
        $offset = max(0, (int)$request->query->get('offset', 0));

        $dealerships = $dealershipRepository->findNearest(
            $coords['lat'],
            $coords['lon'],
            $limit,
            $offset
        );

        return new JsonResponse($dealerships);
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