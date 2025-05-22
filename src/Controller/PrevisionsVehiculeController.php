<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PrevisionsVehiculeController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private array $services;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->services = $this->loadServicesFromCsv();
    }

    private function loadServicesFromCsv(): array
    {
        $services = [];
        $csvPath = $this->params->get('kernel.project_dir') . '/data/iaData.csv';

        if (($handle = fopen($csvPath, "r")) !== false) {
            fgetcsv($handle); // skip header
            while (($data = fgetcsv($handle)) !== false) {
                $services[] = [
                    'name' => $data[1] ?? '',
                    'category' => $data[2] ?? '',
                    'additionnal_help' => $data[3] ?? '',
                    'additionnal_comment' => $data[4] ?? '',
                    'time_unit' => $data[5] ?? '',
                    'price' => $data[6] ?? '',
                ];
            }
            fclose($handle);
        }

        return $services;
    }

    #[Route('/api/previsions', name: 'previsions', methods: ['POST'])]
    public function predict(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['vehicule'])) {
            return $this->json(['error' => 'Missing vehicule data'], 400);
        }

        $vehicule = $payload['vehicule'];

        // Sélection de 5 opérations aléatoires depuis le CSV
        $operations = $this->services;
        $selected = array_slice($operations, 0, 5);

        // Construction de la liste pour le prompt
        $operationList = '';
        foreach ($selected as $op) {
            $operationList .= "- {$op['name']}\n";
        }

        // Prompt pour l'IA
        $prompt = $prompt = <<<EOT
        Tu es un assistant d’entretien automobile.

        Voici un extrait du catalogue d’opérations possibles à faire sur un véhicule :
        $operationList

        Le véhicule a ces caractéristiques :  
        - Marque : {$vehicule['make']}  
        - Modèle : {$vehicule['model']}  
        - Année : {$vehicule['year']}  
        - Kilométrage actuel : {$vehicule['mileage']} km

        Parmi ces opérations, sélectionne les 5 plus pertinentes **en fonction du kilométrage actuel** et de l’entretien typique d’un véhicule de ce type.
        Tu dois favoriser les opérations critiques pour la sécurité ou l’usure (freins, vidange, pneus, etc.).
        Évite les doublons et les interventions mineures ou saisonnières (essuie-glaces, etc.) si des opérations plus importantes sont à prévoir.
        Pour chaque opération, indique **à combien de kilomètres elle devrait être réalisée** (valeur numérique uniquement, supérieure au kilométrage actuel et proche de la réalité).
        Réponds au format JSON suivant (et rien d’autre, sans Markdown, sans commentaires) :
        [
        { "operation": "Nom de l’opération du CSV", "predicted_km": 123456 },
        ...
        ]
        EOT;

        // Appel à l'API Gemini
        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyB9im4A-p34XTqFXgoyOpItPvgGot9HecE';

        try {
            $response = $this->httpClient->request('POST', $apiUrl, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]],
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $text = trim($text);
            if (str_starts_with($text, '```')) {
                $text = preg_replace('/^```[a-z]*\s*/', '', $text);
                $text = preg_replace('/```$/', '', $text);
            }

            // Parser le JSON
            $rawPredictions = json_decode($text, true);

            if (!is_array($rawPredictions)) {
                return $this->json([
                    'error' => 'Réponse de l’IA invalide',
                    'raw' => $text
                ], 500);
            }

            $predictions = array_filter($rawPredictions, function ($item) {
                return isset($item['operation'], $item['predicted_km']) && is_numeric($item['predicted_km']);
            });

            return $this->json([
                'type' => 'predicted_km',
                'vehicule' => $vehicule,
                'predictions' => array_values($predictions)
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur Gemini: ' . $e->getMessage()], 502);
        }
    }
}
