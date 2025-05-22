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
                    'id' => $data[0] ?? '',
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

        $validServices = [];

        foreach ($this->services as $service) {
            $name = strtolower($service['name']);

            // Recherche des valeurs numériques en km et mois
            preg_match_all('/(\d{1,3}(?:[.,\s]?\d{3})*)[ ]*km/i', $name, $kmMatches);
            preg_match_all('/(\d+)[ ]*mois/i', $name, $moisMatches);

            // Nettoyage et conversion
            $kmValues = array_map(fn($v) => (int) str_replace(['.', ',', ' '], '', $v), $kmMatches[1]);
            $maxKm = !empty($kmValues) ? max($kmValues) : null;

            $moisValues = array_map('intval', $moisMatches[1]);
            $maxMois = !empty($moisValues) ? max($moisValues) : null;

            // Vérification des seuils atteints
            $isKmValid = $maxKm !== null && $mileage >= $maxKm;
            $isMoisValid = $maxMois !== null && $ageMonths >= $maxMois;

            // Ne garder l'opération que si au moins un seuil est atteint
            if ($isKmValid || $isMoisValid) {
                $key = $service['category'] ?? 'default';
                $score = max($maxKm ?? 0, $maxMois ?? 0);

                if (!isset($validServices[$key]) || $score > $validServices[$key]['score']) {
                    $validServices[$key] = [
                        'service' => $service,
                        'score' => $score,
                    ];
                }
            }
        }

        return array_map(fn($entry) => $entry['service']['name'], array_values($validServices));
    }

    #[Route('/api/suggest-addons', name: 'suggest-addons', methods: ['POST'])]
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

        À partir de cette liste, propose une liste complémentaires pertinentes issues du catalogue par rapport aux informations du véhicule.  
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
                        'id' => $service['id'],
                    ];
                    if (count($suggestions) >= 3) break;
                }
            }

            if (empty($suggestions)) {
                return $this->json(['message' => 'Aucune suggestion pertinente trouvée.']);
            }

            // Fusionner les deux sources
            $mergedSuggestions = $suggestions;

            foreach ($csvSuggestions as $csvName) {
                $alreadyIncluded = false;
                foreach ($mergedSuggestions as $item) {
                    if (strcasecmp($item['operation'], $csvName) === 0) {
                        $alreadyIncluded = true;
                        break;
                    }
                }

                if (!$alreadyIncluded) {
                    foreach ($this->services as $service) {
                        if (strcasecmp($service['name'], $csvName) === 0) {
                            $mergedSuggestions[] = [
                                'operation' => $service['name'],
                                'category' => $service['category'],
                                'additionnal_help' => $service['additionnal_help'],
                                'additionnal_comment' => $service['additionnal_comment'],
                                'time_unit' => $service['time_unit'],
                                'price' => $service['price'],
                                'id' => $service['id'],
                            ];
                            break;
                        }
                    }
                }
            }

            return $this->json([
                'type' => 'addons',
                'ai_suggestions' => $mergedSuggestions,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Gemini call failed: ' . $e->getMessage()], 502);
        }
    }
}
