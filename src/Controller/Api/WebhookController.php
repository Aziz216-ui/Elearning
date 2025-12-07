<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    /**
     * @Route("/webhook/ninjarmm", name="webhook_ninjarmm", methods={"POST", "GET"})
     */
    public function ninjaRMMWebhook(Request $request): JsonResponse
    {
        // Log the incoming webhook
        $content = $request->getContent();
        $headers = $request->headers->all();
        
        // For debugging - log to file
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_webhook.log',
            date('Y-m-d H:i:s') . " - Webhook received\n" .
            "Headers: " . json_encode($headers) . "\n" .
            "Content: " . $content . "\n\n",
            FILE_APPEND
        );

        // Parse webhook data
        try {
            $data = json_decode($content, true);
            
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Process different webhook types
            $webhookType = $data['type'] ?? 'unknown';
            
            switch ($webhookType) {
                case 'DEVICE_ONLINE':
                    $this->handleDeviceOnline($data);
                    break;
                case 'DEVICE_OFFLINE':
                    $this->handleDeviceOffline($data);
                    break;
                case 'ALERT':
                    $this->handleAlert($data);
                    break;
                case 'SOFTWARE_INSTALLED':
                    $this->handleSoftwareInstalled($data);
                    break;
                default:
                    $this->handleGenericWebhook($data);
                    break;
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'type' => $webhookType,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Webhook processing failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleDeviceOnline(array $data): void
    {
        $deviceId = $data['device_id'] ?? null;
        $deviceName = $data['device_name'] ?? 'Unknown';
        
        // Log device online event
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_events.log',
            date('Y-m-d H:i:s') . " - Device ONLINE: {$deviceName} (ID: {$deviceId})\n",
            FILE_APPEND
        );

        // Here you can:
        // 1. Update database
        // 2. Send notifications
        // 3. Trigger other actions
    }

    private function handleDeviceOffline(array $data): void
    {
        $deviceId = $data['device_id'] ?? null;
        $deviceName = $data['device_name'] ?? 'Unknown';
        
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_events.log',
            date('Y-m-d H:i:s') . " - Device OFFLINE: {$deviceName} (ID: {$deviceId})\n",
            FILE_APPEND
        );
    }

    private function handleAlert(array $data): void
    {
        $alertType = $data['alert_type'] ?? 'Unknown';
        $severity = $data['severity'] ?? 'Unknown';
        $message = $data['message'] ?? 'No message';
        
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_events.log',
            date('Y-m-d H:i:s') . " - ALERT: {$alertType} - {$severity} - {$message}\n",
            FILE_APPEND
        );
    }

    private function handleSoftwareInstalled(array $data): void
    {
        $softwareName = $data['software_name'] ?? 'Unknown';
        $deviceId = $data['device_id'] ?? null;
        
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_events.log',
            date('Y-m-d H:i:s') . " - Software installed: {$softwareName} on device {$deviceId}\n",
            FILE_APPEND
        );
    }

    private function handleGenericWebhook(array $data): void
    {
        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/log/ninjarmm_events.log',
            date('Y-m-d H:i:s') . " - Generic webhook: " . json_encode($data) . "\n",
            FILE_APPEND
        );
    }

    /**
     * @Route("/webhook/test", name="webhook_test", methods={"GET"})
     */
    public function testWebhook(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => 'Webhook endpoint is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'available_endpoints' => [
                '/webhook/ninjarmm' => 'POST/GET - NinjaRMM webhook endpoint',
                '/webhook/test' => 'GET - Test endpoint'
            ]
        ]);
    }
}
