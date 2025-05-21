<?php

namespace App\Controller\Api;

use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VehicleController extends AbstractController
{
    #[Route('/api/vehicles/by-registration', name: 'create_vehicle', methods: ['POST'])]
    public function create(
        Request $request,
        HttpClientInterface $httpClient,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $plate = $data['registrationNumber'] ?? null;

        if (!$plate) {
            return $this->json(['error' => 'registrationNumber is required'], 400);
        }


        try {
            $response = $httpClient->request('GET', 'https://api-plaque-immatriculation-siv.p.rapidapi.com/get-vehicule-info', [
                'query' => [
                    'token' => 'TokenDemoRapidapi', // ou ta vraie clé si tu en as une
                    'host_name' => 'https://apiplaqueimmatriculation.com',
                    'immatriculation' => $plate
                ],
                'headers' => [
                    'x-rapidapi-key' => '92d7cdfcd7mshc96d3962cc8be3fp147d8cjsnfffb5ffb88cd',
                    'x-rapidapi-host' => 'api-plaque-immatriculation-siv.p.rapidapi.com'
                ]
            ]);
            

            $result = $response->toArray();
            $vehicleData = $result['data'];

            // Créer un nouvel objet Vehicle
            $vehicle = new Vehicle();
            $vehicle->setRegistrationNumber($vehicleData['immat'] ?? $plate);
            $vehicle->setBrand($vehicleData['marque'] ?? '');
            $vehicle->setModel($vehicleData['modele'] ?? '');
            $vehicle->setVin($vehicleData['vin'] ?? '');
            $vehicle->setMileage(0); // à remplacer si dispo

            // Date 1ère mise en circulation
            if (!empty($vehicleData['date1erCir_fr'])) {
                $date = \DateTime::createFromFormat('d-m-Y', $vehicleData['date1erCir_fr']);
                if ($date) {
                    $vehicle->setFirstRegistrationDate($date);
                }
            }

            $em->persist($vehicle);
            $em->flush();

            return $this->json($vehicle, 201, [], ['groups' => ['vehicle:read']]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'API request failed or invalid plate',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
