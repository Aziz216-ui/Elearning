<?php

namespace App\Controller\Webhook;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class NinjaRMMWebhookController extends AbstractController
{
    public function handle(Request $request): Response
    {
        // Récupérer le contenu brut
        $payload = $request->getContent();
        
        // Décoder le JSON
        $data = json_decode($payload, true);
        
        // Vérifier si le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload'], 
                Response::HTTP_BAD_REQUEST
            );
        }

        // Créer le répertoire de logs s'il n'existe pas
        $logDir = $this->getParameter('kernel.project_dir') . '/var/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Enregistrer les données brutes
        file_put_contents(
            $logDir . '/ninjarmm_webhook.log',
            date('[Y-m-d H:i:s]') . " Webhook received:\n" . 
            "Headers: " . json_encode($request->headers->all()) . "\n" .
            "Payload: " . $payload . "\n\n",
            FILE_APPEND
        );

        // Traiter le type d'événement
        $eventType = $data['eventType'] ?? 'unknown';
        $deviceId = $data['deviceId'] ?? null;
        
        // Enregistrer l'événement spécifique
        file_put_contents(
            $logDir . '/ninjarmm_events.log',
            sprintf(
                "[%s] Event: %s | Device: %s\n",
                date('Y-m-d H:i:s'),
                $eventType,
                $deviceId ?: 'N/A'
            ),
            FILE_APPEND
        );

        // Répondre avec succès
        return new Response('OK', Response::HTTP_OK);
    }
}
