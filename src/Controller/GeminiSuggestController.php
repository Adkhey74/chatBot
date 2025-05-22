<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiSuggestController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;

    /** @var array<int,array<string,string>> */
    private array $services;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->services = $this->loadServicesFromCsv();
    }

    /**
     * Charge le catalogue CSV en mémoire.
     */
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
        $path = $this->params->get('kernel.project_dir') . '/data/iaData.csv';
        $out = [];

        if (($h = @fopen($path, 'r')) !== false) {
            fgetcsv($h); // ignore header
            while (($row = fgetcsv($h)) !== false) {
                $out[] = [
                    'name' => $row[1] ?? '',
                    'category' => $row[2] ?? '',
                    'additionnal_help' => $row[3] ?? '',
                    'additionnal_comment' => $row[4] ?? '',
                    'time_unit' => $row[5] ?? '',
                    'price' => $row[6] ?? '',
                ];
            }
            fclose($h);
        }
        return $out;
    }

    /**
     * Retourne les libellés suggérés via règles km / mois.
     */
    private function getCsvSuggestions(array $vehicule): array
    {
        $km = (int) ($vehicule['mileage'] ?? 0);
        $year = (int) ($vehicule['year'] ?? date('Y'));
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
        $res = [];
        foreach ($this->services as $s) {
            $lower = strtolower($s['name']);
            if (preg_match('/(\d+)\s*km/i', $lower, $kmMatch) && (int) $kmMatch[1] <= $km) {
                $res[] = $s['name'];
            }
            if (preg_match('/(\d+)\s*mois/i', $lower, $mMatch) && (int) $mMatch[1] <= $ageMonths) {
                $res[] = $s['name'];
            }
        }
        return array_unique($res);
    }

    #[Route('/api/suggest-addons', name: 'suggest-addons', methods: ['POST'])]
    public function suggest(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!isset($payload['vehicule'], $payload['operation'])) {
            return $this->json(['error' => 'Missing vehicule or operation'], 400);
        }

        $vehicule = $payload['vehicule'];
        $operation = $payload['operation'];
        $csvSuggestions = $this->getCsvSuggestions($vehicule);

        // Construit la liste du catalogue pour le prompt
        $catalog = '';
        foreach ($this->services as $s) {
            $catalog .= "- {$s['name']} ({$s['price']} €, {$s['time_unit']})\n";
        }

        $prompt = <<<PROMPT
            Tu es un assistant après-vente automobile.
            Voici la liste des opérations :

            $catalog

            Données véhicule :
            - Marque : {$vehicule['make']}
            - Modèle : {$vehicule['model']}
            - Année  : {$vehicule['year']}
            - Kilométrage : {$vehicule['mileage']} km

            Opération principale : {$operation}.

            Propose jusqu’à 3 opérations complémentaires pertinentes issues du catalogue et renvoie UNIQUEMENT un tableau JSON où chaque objet contient "name" et "reason".
            PROMPT;

        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyB9im4A-p34XTqFXgoyOpItPvgGot9HecE';

        $aiSuggestions = [];
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
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

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
            // Nettoyage éventuel des ```json ... ```
            $rawText = trim($rawText);
            if (str_starts_with($rawText, '```')) {
                $rawText = preg_replace('/^```[a-zA-Z]*\s*|```$/', '', $rawText);
                $rawText = trim($rawText);
            }

            $rows = json_decode($rawText, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Fallback "Nom : raison"
            $rows = [];
            foreach (preg_split('/\r?\n/', $rawText ?? '') as $line) {
                if (strpos($line, ':') !== false) {
                    [$n, $r] = array_map('trim', explode(':', $line, 2));
                    $rows[] = ['name' => $n, 'reason' => $r];
                }
            }
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Gemini call failed: ' . $e->getMessage()], 502);
        }

        // Associe aux services existants
        foreach ($rows as $row) {
            foreach ($this->services as $s) {
                if (strcasecmp($s['name'], $row['name'] ?? '') === 0) {
                    $aiSuggestions[] = [
                        'operation' => $s['name'],
                        'category' => $s['category'],
                        'additionnal_help' => $s['additionnal_help'],
                        'additionnal_comment' => $s['additionnal_comment'],
                        'time_unit' => $s['time_unit'],
                        'price' => $s['price'],
                        'reason' => $row['reason'] ?? '',
                    ];
                    break;
                }
            }
            if (count($aiSuggestions) >= 3) {
                break;
            }
        }

        return $this->json([
            'type' => 'addons',
            'csv_based' => $csvSuggestions,
            'ai_suggestions' => $aiSuggestions,
        ]);
    }
}
