<?php
namespace App\Controller;

use Google_Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DialogflowWebhookController extends AbstractController
{
    public function handle(Request $request): JsonResponse
    {
        // 1) Lire le JSON envoyé par Dialogflow CX
        $payload = json_decode($request->getContent(), true);
        $params  = $payload['sessionInfo']['parameters'] ?? [];

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->getParameter('kernel.project_dir') . '/config/credentials/dialogflow.json');

        // 2) Construire le prompt pour Gemini
        $prompt = sprintf(
            "Confirme un rendez-vous le %s à %s pour un service %s sur le véhicule immatriculé %s.",
            $params['date']  ?? 'non spécifié',
            $params['time']  ?? 'non spécifié',
            $params['service'] ?? 'non spécifié',
            $params['immat'] ?? 'non spécifié'
        );

        // 3) Obtenir un token OAuth2 via google/apiclient
        $gClient = new Google_Client();
        $gClient->useApplicationDefaultCredentials();
        $gClient->addScope('https://www.googleapis.com/auth/cloud-platform');
        $token  = $gClient->fetchAccessTokenWithAssertion()['access_token'];

        // 4) Appeler l’API Generative de Vertex AI
        $http = HttpClient::create();
        $url = sprintf(
            'https://us-central1-aiplatform.googleapis.com/v1/projects/%s/locations/us-central1/publishers/google/models/gemini-pro:generateContent',
            $this->getParameter('google_cloud_project')
        );
        
        $response = $http->request('POST', $url, [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ],
        ]);
        

        $body = $response->toArray();
        $text = $body['candidates'][0]['output'] ?? 'Désolé, je n’ai pas pu formuler de réponse.';

        // 5) Retourner le format attendu par Dialogflow CX
        $fulfillment = [
            'fulfillment_response' => [
                'messages' => [
                    ['text' => ['text' => [$text]]]
                ]
            ]
        ];

        return new JsonResponse($fulfillment);
    }
}