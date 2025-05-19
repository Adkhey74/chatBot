<?php
namespace App\Controller\Api;

use Google_Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DialogflowWebhookController extends AbstractController
{
    public function handle(Request $request): JsonResponse
    {
        try {
            // 1) Récupère le payload de Dialogflow CX
            $payload = json_decode($request->getContent(), true);
            $p = $payload['sessionInfo']['parameters'] ?? [];

            // 2) Construit le prompt
            $prompt = sprintf(
                "Confirme un rendez-vous le %s à %s pour un service %s sur le véhicule immatriculé %s.",
                $p['date'] ?? 'non spécifié',
                $p['time'] ?? 'non spécifié',
                $p['service'] ?? 'non spécifié',
                $p['immat'] ?? 'non spécifié'
            );

            // 3) Authentification Google
            $gClient = new Google_Client();
            $credentialsPath = $this->getParameter('kernel.project_dir') . '/config/credentials/dialogflow.json';
            if (!file_exists($credentialsPath)) {
                throw new \RuntimeException("Clé d'API Google introuvable : $credentialsPath");
            }
            $gClient->setAuthConfig($credentialsPath);
            $gClient->useApplicationDefaultCredentials();
            $gClient->addScope('https://www.googleapis.com/auth/cloud-platform');

            $tokenResp = $gClient->fetchAccessTokenWithAssertion();
            if (isset($tokenResp['error'])) {
                throw new \RuntimeException("Erreur d'authentification Google : " . json_encode($tokenResp));
            }
            $accessToken = $tokenResp['access_token'];

            // 4) Appel Vertex AI Generative
            $project = $_ENV['GOOGLE_CLOUD_PROJECT']
                ?? $_SERVER['GOOGLE_CLOUD_PROJECT']
                ?? null;
            if (!$project) {
                throw new \RuntimeException("La variable d'environnement GOOGLE_CLOUD_PROJECT n'est pas définie.");
            }

            $region = 'us-central1';           // adapter selon votre région
            $modelId = 'gemini-2.0-flash-001';   // modèle stable le plus récent
            $url = sprintf(
                'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s'
                . '/publishers/google/models/%s:generateContent',
                $region,
                $project,
                $region,
                $modelId,
            );

            $http = HttpClient::create();
            $resp = $http->request('POST', $url, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'candidateCount' => 1,
                        'maxOutputTokens' => 256,
                    ],
                ],
            ]);

            $status = $resp->getStatusCode();
            $body = json_decode($resp->getContent(false), true);

            if ($status !== 200 || !isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \RuntimeException("Vertex AI a retourné un statut $status : " . json_encode($body));
            }

            // 5) Extraction du texte généré
            $text = $body['candidates'][0]['content']['parts'][0]['text'];

            // 6) Formate et renvoie la réponse à Dialogflow CX
            return new JsonResponse([
                'fulfillment_response' => [
                    'messages' => [
                        ['text' => ['text' => [$text]]],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
