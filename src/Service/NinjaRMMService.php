<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NinjaRMMService
{
    private $httpClient;
    private $params;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->baseUrl = $params->get('NINJARM_API_BASE_URL');
        $this->clientId = $params->get('NINJARM_API_CLIENT_ID');
        $this->clientSecret = $params->get('NINJARM_API_CLIENT_SECRET');
        $this->accessToken = $params->get('NINJARM_API_ACCESS_TOKEN');
    }

    /**
     * Get authentication token
     */
    public function getAuthToken(): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://app.ninjarmm.com/oauth/token', [
                'json' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->client_secret,
                    'scope' => 'monitoring'
                ]
            ]);

            $data = $response->toArray();
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            throw new \Exception('Failed to get auth token: ' . $e->getMessage());
        }
    }

    /**
     * Get all devices
     */
    public function getDevices(): array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            throw new \Exception('Unable to obtain authentication token');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/devices', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get devices: ' . $e->getMessage());
        }
    }

    /**
     * Get device by ID
     */
    public function getDevice(string $deviceId): array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            throw new \Exception('Unable to obtain authentication token');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/devices/' . $deviceId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get device: ' . $e->getMessage());
        }
    }

    /**
     * Get system metrics for a device
     */
    public function getDeviceMetrics(string $deviceId): array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            throw new \Exception('Unable to obtain authentication token');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/devices/' . $deviceId . '/system-metrics', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get device metrics: ' . $e->getMessage());
        }
    }

    /**
     * Get alerts
     */
    public function getAlerts(): array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            throw new \Exception('Unable to obtain authentication token');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/alerts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get alerts: ' . $e->getMessage());
        }
    }

    /**
     * Get software inventory for a device
     */
    public function getDeviceSoftware(string $deviceId): array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            throw new \Exception('Unable to obtain authentication token');
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/devices/' . $deviceId . '/software', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Failed to get device software: ' . $e->getMessage());
        }
    }
}
