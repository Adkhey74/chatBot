<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeminiController extends AbstractController
{
  private HttpClientInterface $httpClient;
  private array $services;
  private ParameterBagInterface $params;

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
      fgetcsv($handle, 1000, ","); // skip header

      while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if (isset($data[0], $data[1])) {
          $services[] = [
            'id' => $data[0],
            'name' => $data[1],
            'category' => $data[2] ?? '',
            'additionnal_help' => $data[3] ?? '',
            'additionnal_comment' => $data[4] ?? '',
            'time_unit' => $data[5] ?? '',
            'price' => $data[6] ?? ''
          ];
        }
      }
      fclose($handle);
    }

    return $services;
  }

  #[Route('/api/generate-text', name: 'generate_text', methods: ['POST'])]
  public function generate(Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), true);
    $userText = trim($data['text'] ?? '');

    if ($userText === '') {
      return $this->json([
        'type' => 'service',
        'content' => 'Le champ "text" est requis.',
        'services' => [],
      ], JsonResponse::HTTP_BAD_REQUEST);
    }

    $context = "Tu es un assistant virtuel spécialisé dans la prise de rendez-vous et la proposition de services pour un atelier automobile.\n\n";
    $context .= "Voici la liste des opérations disponibles :\n";
    foreach ($this->services as $index => $service) {
      $context .= ($index + 1) . ". " . $service['name'] . " (" . $service['category'] . ")\n";
    }
    $context .= "\nInstructions:\n";
    $context .= "- Si l'utilisateur demande un de ces services, répond avec le nom exact de l'opération.\n";
    $context .= "- Si l'utilisateur demande autre chose, répond que ce service n'est pas proposé.";

    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyB9im4A-p34XTqFXgoyOpItPvgGot9HecE';

    $primaryPayload = [
      'contents' => [
        [
          'role' => 'user',
          'parts' => [
            ['text' => $context . "\n\n" . $userText],
          ],
        ],
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', $apiUrl, [
        'headers' => ['Content-Type' => 'application/json'],
        'json' => $primaryPayload,
        'timeout' => 60,
      ]);

      $responseData = $response->toArray(false);
      $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

      $matchedServices = [];

      foreach ($this->services as $service) {
        if (stripos($responseText, $service['name']) !== false && $service['additionnal_comment'] != 'NULL') {
          return $this->json([
            'type' => 'general',
            'content' => $service['additionnal_comment'],
          ]);
        } else if (stripos($responseText, $service['name']) !== false) {
          $matchedServices[] = [
            'id' => (string) $service['id'],
            'operation' => $service['name'],
            'category' => $service['category'],
            'additionnal_help' => $service['additionnal_help'],
            'additionnal_comment' => $service['additionnal_comment'],
            'time_unit' => $service['time_unit'],
            'price' => $service['price'],
          ];
        }
      }

      if (!empty($matchedServices)) {
        return $this->json([
          'type' => 'service',
          'content' => count($matchedServices) === 1
            ? "Voici le service correspondant à votre demande."
            : "Plusieurs services correspondent à votre demande, veuillez en sélectionner un.",
          'services' => $matchedServices,
        ]);
      }

      // Fallback si aucun service trouvé
      $generalContext = <<<EOT
            Tu es un assistant virtuel automobile. Tu réponds uniquement aux questions liées aux véhicules (voitures, moteurs, pièces, réparations, entretien, etc.).

            Si la question n’est pas liée à l’automobile, tu dois répondre :
            "Je suis un assistant automobile et je ne peux répondre qu’à des questions liées aux véhicules."
        EOT;

      $fallbackPayload = [
        'contents' => [
          [
            'role' => 'user',
            'parts' => [
              ['text' => $generalContext . "\n\n" . $userText . "\n\nRéponds en maximum 3 lignes."],
            ],
          ],
        ],
      ];

      $fallbackResponse = $this->httpClient->request('POST', $apiUrl, [
        'headers' => ['Content-Type' => 'application/json'],
        'json' => $fallbackPayload,
        'timeout' => 60,
      ]);

      $fallbackData = $fallbackResponse->toArray(false);
      $fallbackText = $fallbackData['candidates'][0]['content']['parts'][0]['text'] ?? '';

      return $this->json([
        'type' => 'general',
        'content' => $fallbackText,
      ]);
    } catch (\Exception $e) {
      return $this->json([
        'type' => 'service',
        'content' => 'Erreur lors de l’appel à Gemini : ' . $e->getMessage(),
        'services' => [],
      ], JsonResponse::HTTP_BAD_GATEWAY);
    }
  }
}

