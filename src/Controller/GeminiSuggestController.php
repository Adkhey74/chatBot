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
        $path = $this->params->get('kernel.project_dir') . '/data/iaData.csv';
        $out = [];

        if (($h = @fopen($path, 'r')) !== false) {
            fgetcsv($h); // ignore header
            while (($row = fgetcsv($h)) !== false) {
                $out[] = [
                    'id' => $row[0] ?? '',
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
                        'id' => $s['id'],
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
