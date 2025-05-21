<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeminiSuggestController extends AbstractController
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
        $csvPath = $this->params->get('kernel.project_dir') . '/data/carOperation.csv';

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

    private function getCsvSuggestions(array $vehicule): array
    {
        $mileage = $vehicule['mileage'] ?? 0;
        $year = $vehicule['year'] ?? date('Y');
        $ageMonths = (date('Y') - $year) * 12;

        $matches = [];

        foreach ($this->services as $service) {
            $name = strtolower($service['name']);

            if (preg_match('/(\d+)[ ]*km/i', $name, $kmMatch) && $kmMatch[1] <= $mileage) {
                $matches[] = $service['name'];
            }

            if (preg_match('/(\d+)[ ]*mois/i', $name, $moisMatch) && $moisMatch[1] <= $ageMonths) {
                $matches[] = $service['name'];
            }
        }

        return array_unique($matches);
    }

    #[Route('/api/suggest-addons', name: 'suggest-addons', methods: ['GET'])]
    public function suggest(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['vehicule']) || !isset($payload['operation'])) {
            return $this->json(['error' => 'Missing vehicule or operation'], 400);
        }

        $vehicule = $payload['vehicule'];
        $operation = $payload['operation'];
        $csvSuggestions = $this->getCsvSuggestions($vehicule);

        // Génération d'une liste lisible des opérations à insérer dans le prompt
        $operationsList = '';
        foreach ($this->services as $service) {
            $operationsList .= "- {$service['name']} ({$service['price']} €, {$service['time_unit']})\n";
        }

        $prompt = <<<EOT
        Tu es un assistant après-vente automobile. Voici la liste des opérations disponibles dans le catalogue :

        $operationsList

        Le véhicule a ces caractéristiques :  
        - Marque : {$vehicule['make']}  
        - Modèle : {$vehicule['model']}  
        - Année : {$vehicule['year']}  
        - Kilométrage : {$vehicule['mileage']} km

        L’opération principale prévue est : $operation.

        À partir de cette liste, propose jusqu’à 3 opérations complémentaires pertinentes issues du catalogue.  
        Ne propose que des noms présents dans la liste.
        Réponds uniquement avec une liste simple, sans explication.
        EOT;

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

            // Extraction des suggestions correspondant aux services connus
            $suggestions = [];
            foreach ($this->services as $service) {
                if (stripos($text, $service['name']) !== false) {
                    $suggestions[] = [
                        'operation' => $service['name'],
                        'category' => $service['category'],
                        'additionnal_help' => $service['additionnal_help'],
                        'additionnal_comment' => $service['additionnal_comment'],
                        'time_unit' => $service['time_unit'],
                        'price' => $service['price'],
                    ];
                    if (count($suggestions) >= 3) break;
                }
            }

            if (empty($suggestions)) {
                return $this->json(['message' => 'Aucune suggestion pertinente trouvée.']);
            }

            return $this->json([
                'type' => 'addons',
                'csv_based' => $csvSuggestions,
                'ai_suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Gemini call failed: ' . $e->getMessage()], 502);
        }
    }
}
