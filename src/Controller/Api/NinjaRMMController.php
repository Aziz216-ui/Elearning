<?php

namespace App\Controller\Api;

use App\Service\NinjaRMMService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NinjaRMMController extends AbstractController
{
    private $ninjarmmService;

    public function __construct(NinjaRMMService $ninjarmmService)
    {
        $this->ninjarmmService = $ninjarmmService;
    }

    /**
     * @Route("/api/ninjarmm/devices", name="api_ninjarmm_devices", methods={"GET"})
     */
    public function getDevices(): JsonResponse
    {
        try {
            $devices = $this->ninjarmmService->getDevices();
            return new JsonResponse([
                'success' => true,
                'data' => $devices
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/ninjarmm/devices/{id}", name="api_ninjarmm_device", methods={"GET"})
     */
    public function getDevice(string $id): JsonResponse
    {
        try {
            $device = $this->ninjarmmService->getDevice($id);
            return new JsonResponse([
                'success' => true,
                'data' => $device
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/ninjarmm/devices/{id}/metrics", name="api_ninjarmm_device_metrics", methods={"GET"})
     */
    public function getDeviceMetrics(string $id): JsonResponse
    {
        try {
            $metrics = $this->ninjarmmService->getDeviceMetrics($id);
            return new JsonResponse([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/ninjarmm/alerts", name="api_ninjarmm_alerts", methods={"GET"})
     */
    public function getAlerts(): JsonResponse
    {
        try {
            $alerts = $this->ninjarmmService->getAlerts();
            return new JsonResponse([
                'success' => true,
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/ninjarmm/devices/{id}/software", name="api_ninjarmm_device_software", methods={"GET"})
     */
    public function getDeviceSoftware(string $id): JsonResponse
    {
        try {
            $software = $this->ninjarmmService->getDeviceSoftware($id);
            return new JsonResponse([
                'success' => true,
                'data' => $software
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/ninjarmm/dashboard", name="api_ninjarmm_dashboard", methods={"GET"})
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $devices = $this->ninjarmmService->getDevices();
            $alerts = $this->ninjarmmService->getAlerts();

            // Process data for dashboard
            $totalDevices = count($devices);
            $onlineDevices = 0;
            $offlineDevices = 0;
            $criticalAlerts = 0;

            foreach ($devices as $device) {
                if ($device['status'] === 'ONLINE') {
                    $onlineDevices++;
                } else {
                    $offlineDevices++;
                }
            }

            foreach ($alerts as $alert) {
                if ($alert['severity'] === 'CRITICAL') {
                    $criticalAlerts++;
                }
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'totalDevices' => $totalDevices,
                    'onlineDevices' => $onlineDevices,
                    'offlineDevices' => $offlineDevices,
                    'criticalAlerts' => $criticalAlerts,
                    'devices' => array_slice($devices, 0, 10), // Last 10 devices
                    'alerts' => array_slice($alerts, 0, 5)   // Last 5 alerts
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
