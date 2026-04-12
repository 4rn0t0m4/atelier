<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getAccessToken(): ?string
    {
        $token = Cache::get('paypal_access_token');

        if ($token) {
            return $token;
        }

        $response = Http::asForm()
            ->withBasicAuth(
                config('services.paypal.client_id'),
                config('services.paypal.client_secret')
            )
            ->post($this->baseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            $token = $response->json('access_token');
            $expiresIn = $response->json('expires_in', 3600) - 60;
            Cache::put('paypal_access_token', $token, $expiresIn);

            return $token;
        }

        Log::error('PayPal: échec obtention token', ['body' => $response->body()]);

        return null;
    }

    /**
     * Crée une commande PayPal.
     *
     * @return array{id: ?string, error: ?string}
     */
    public function createOrder(float $total, string $orderNumber): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['id' => null, 'error' => 'Impossible de se connecter à PayPal.'];
        }

        try {
            $response = Http::withToken($token)
                ->asJson()
                ->acceptJson()
                ->post($this->baseUrl() . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $orderNumber,
                            'description' => "Commande {$orderNumber} - Atelier d'Aubin",
                            'amount' => [
                                'currency_code' => 'EUR',
                                'value' => number_format($total, 2, '.', ''),
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return ['id' => $response->json('id'), 'error' => null];
            }

            Log::error('PayPal: échec création commande', ['body' => $response->json()]);

            return ['id' => null, 'error' => 'Erreur PayPal : ' . ($response->json('message') ?? $response->status())];
        } catch (\Throwable $e) {
            Log::error('PayPal: exception création commande', ['error' => $e->getMessage()]);

            return ['id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Capture le paiement d'une commande PayPal.
     *
     * @return array{success: bool, error: ?string}
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return ['success' => false, 'error' => 'Impossible de se connecter à PayPal.'];
        }

        try {
            $response = Http::withToken($token)
                ->contentType('application/json')
                ->acceptJson()
                ->post($this->baseUrl() . "/v2/checkout/orders/{$paypalOrderId}/capture");

            if ($response->successful() && $response->json('status') === 'COMPLETED') {
                return ['success' => true, 'error' => null];
            }

            Log::error('PayPal: échec capture', ['body' => $response->json()]);

            return ['success' => false, 'error' => 'Paiement non complété : ' . ($response->json('status') ?? $response->status())];
        } catch (\Throwable $e) {
            Log::error('PayPal: exception capture', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
