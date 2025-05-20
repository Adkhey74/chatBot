<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GeminiController extends AbstractController
{
  private HttpClientInterface $httpClient;

  public function __construct(HttpClientInterface $httpClient)
  {
    $this->httpClient = $httpClient;
  }

  /**
   * @Route("/generate-text", name="generate_text", methods={"POST"})
   */
  #[Route('/api/generate-text', name: 'user_drivers', methods: ['GET'])]

  public function generate(Request $request): JsonResponse
  {
    $data     = json_decode($request->getContent(), true);
    $userText = trim($data['text'] ?? '');
    $context   = "Tu es un assistant virtuel spécialisé dans la prise de rendez-vous et la proposition de services pour un atelier automobile.  
Voici la liste des opérations disponibles :  
42. Rendez-vous carrosserie  
43. Réparation impact pare-brise  
44. Service Embrayage  
45. Service recherche de panne diagnostic  
46. Remplacement de pare-brise  
47. Teinte des vitres  
48. Service bougies d'allumage  
49. Service nettoyage climatisation  
50. Service Essuie-glaces avant  
51. Service Essuie-glaces arrière  
52. Service recharge de climatisation R134A  
53. Service recharge de climatisation R1234yf  
54. Service climatisation  
55. Service désodorisant climatisation  
56. Demande de rappel  
57. Passage au banc de diagnostic  

Instructions:  
- Si l'utilisateur demande un de ces services, répond avec le nom exact de l'opération.
- Si l'utilisateur demande autre chose, répond que ce service n'est pas proposé.";

    if ('' === trim($userText)) {
      return $this->json([
        'error' => 'Le champ "text" est requis.',
      ], JsonResponse::HTTP_BAD_REQUEST);
    }

    $payload = [
      'contents' => [
        [
          'role'  => 'user',
          'parts' => [
            ['text' => $context . "\n\n" . $userText],
          ],
        ],
      ],
    ];

    // URL de votre API Gemini
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyB9im4A-p34XTqFXgoyOpItPvgGot9HecE';

    try {
      $response = $this->httpClient->request('POST', $apiUrl, [
        'headers' => ['Content-Type' => 'application/json'],
        'json'    => $payload,
        'timeout' => 60,
      ]);

      $data = $response->toArray(false);

      // Extraire le texte de la réponse
      $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

      // Vérifier si la réponse correspond à un service de la liste
      $services = [
        'Rendez-vous carrosserie',
        'Réparation impact pare-brise',
        'Service Embrayage',
        'Service recherche de panne diagnostic',
        'Remplacement de pare-brise',
        'Teinte des vitres',
        'Service bougies d\'allumage',
        'Service nettoyage climatisation',
        'Service Essuie-glaces avant',
        'Service Essuie-glaces arrière',
        'Service recharge de climatisation R134A',
        'Service recharge de climatisation R1234yf',
        'Service climatisation',
        'Service désodorisant climatisation',
        'Demande de rappel',
        'Passage au banc de diagnostic'
      ];

      $isService = false;
      foreach ($services as $service) {
        if (stripos($responseText, $service) !== false) {
          return $this->json(['operation' => $service]);
        }
      }

      return $this->json(['error' => 'Ce service n\'est pas proposé']);
    } catch (\Exception $e) {
      return $this->json([
        'error' => 'Erreur lors de l\'appel à Gemini : ' . $e->getMessage(),
      ], JsonResponse::HTTP_BAD_GATEWAY);
    }
  }
}
