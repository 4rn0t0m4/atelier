<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BoxtalShippingService
{
    private function baseUrl(): string
    {
        return rtrim(config('shipping.boxtal.v3_base_url', 'https://api.boxtal.com'), '/');
    }

    private function auth(): string
    {
        return base64_encode(
            config('shipping.boxtal.v3_access_key') . ':' . config('shipping.boxtal.v3_secret_key')
        );
    }

    /**
     * Crée une expédition Boxtal pour la commande.
     *
     * @return array{success: bool, shipping_order_id: ?string, label_url: ?string, error: ?string}
     */
    public function createShipment(Order $order, array $overrides = []): array
    {
        if (! config('shipping.boxtal.v3_access_key')) {
            return ['success' => false, 'shipping_order_id' => null, 'error' => 'BOXTAL_V3_ACCESS_KEY non configuré.'];
        }

        if ($order->shipping_key === 'colissimo') {
            Log::info("BoxtalShipping: commande #{$order->number} Colissimo, push ignoré (gestion manuelle).");

            return ['success' => false, 'shipping_order_id' => null, 'error' => null];
        }

        $payload = $this->buildPayload($order, $overrides);

        Log::debug("BoxtalShipping: payload commande #{$order->number}", $payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl() . '/shipping/v3.1/shipping-order', $payload);

            if ($response->successful()) {
                $data = $response->json('content') ?? $response->json();
                $shippingOrderId = $data['id'] ?? null;

                Log::info("BoxtalShipping: expédition créée pour commande #{$order->number}", [
                    'shipping_order_id' => $shippingOrderId,
                ]);

                $labelUrl = $data['documents'][0]['url']
                    ?? $data['labelUrl']
                    ?? $data['label']['url']
                    ?? null;

                return ['success' => true, 'shipping_order_id' => $shippingOrderId, 'label_url' => $labelUrl, 'error' => null];
            }

            $errorBody = $response->json();
            $errorMsg = $this->formatApiError($errorBody, $response->status());

            Log::error("BoxtalShipping: échec création expédition #{$order->number}", [
                'status' => $response->status(),
                'body' => $errorBody,
            ]);

            return ['success' => false, 'shipping_order_id' => null, 'error' => $errorMsg];
        } catch (\Throwable $e) {
            Log::error("BoxtalShipping: exception pour commande #{$order->number}", [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'shipping_order_id' => null, 'error' => $e->getMessage()];
        }
    }

    private function buildPayload(Order $order, array $overrides): array
    {
        $from = config('shipping.boxtal.from_address');
        $pkg = config('shipping.boxtal.default_package');
        $contentCategoryId = config('shipping.boxtal.content_category_id');

        $offerCode = $overrides['shippingOfferCode']
            ?? $this->resolveOfferCode($order);

        $payload = [
            'shippingOfferCode' => $offerCode,
            'labelType' => $overrides['labelType'] ?? 'PDF_A4',
            'shipment' => [
                'externalId' => $order->number,
                'content' => [
                    'id' => $contentCategoryId,
                    'description' => 'Objets décoratifs en bois',
                ],
                'fromAddress' => [
                    'type' => 'BUSINESS',
                    'contact' => [
                        'company' => $from['company'],
                        'firstName' => $from['firstName'],
                        'lastName' => $from['lastName'],
                        'email' => $from['email'],
                        'phone' => $from['phone'],
                    ],
                    'location' => [
                        'street' => $from['street'],
                        'city' => $from['city'],
                        'postalCode' => $from['postalCode'],
                        'countryIsoCode' => $from['country'],
                    ],
                ],
                'toAddress' => [
                    'type' => 'RESIDENTIAL',
                    'contact' => array_filter([
                        'firstName' => $order->shipping_first_name ?: $order->billing_first_name,
                        'lastName' => $order->shipping_last_name ?: $order->billing_last_name,
                        'email' => $order->billing_email,
                        'phone' => $order->billing_phone ?: null,
                    ]),
                    'location' => [
                        'street' => trim(($order->shipping_address_1 ?: $order->billing_address_1) . ' ' . ($order->shipping_address_2 ?: $order->billing_address_2 ?? '')),
                        'city' => $order->shipping_city ?: $order->billing_city,
                        'postalCode' => $order->shipping_postcode ?: $order->billing_postcode,
                        'countryIsoCode' => $order->shipping_country ?: $order->billing_country ?: 'FR',
                    ],
                ],
                'packages' => [
                    [
                        'weight' => (float) ($overrides['weight'] ?? $pkg['weight']),
                        'length' => (int) ($overrides['length'] ?? $pkg['length']),
                        'width' => (int) ($overrides['width'] ?? $pkg['width']),
                        'height' => (int) ($overrides['height'] ?? $pkg['height']),
                        'value' => [
                            'value' => (float) $order->total,
                            'currency' => 'EUR',
                        ],
                        'content' => [
                            'id' => $contentCategoryId,
                            'description' => 'Objets décoratifs en bois',
                        ],
                    ],
                ],
            ],
        ];

        if ($order->relay_point_code) {
            $payload['shipment']['pickupPointCode'] = $order->relay_point_code;
        }

        return $payload;
    }

    private function resolveOfferCode(Order $order): string
    {
        $offers = config('shipping.boxtal.shipping_offer_codes');

        if ($order->relay_network && isset($offers[$order->relay_network])) {
            return $offers[$order->relay_network];
        }

        if ($order->shipping_key === 'colissimo' && isset($offers['colissimo'])) {
            return $offers['colissimo'];
        }

        return $offers['MONR_NETWORK'] ?? 'MONR-CpourToi';
    }

    public function fetchLabelUrl(string $shippingOrderId): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth(),
                'Accept' => 'application/json',
            ])->get($this->baseUrl() . '/shipping/v3.1/shipping-order/' . $shippingOrderId . '/document');

            Log::debug("BoxtalShipping: récupération étiquette {$shippingOrderId}", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Réponse directe avec url
                if (isset($data['url'])) {
                    return $data['url'];
                }

                // Tableau de documents (content ou racine)
                $docs = $data['content'] ?? $data['documents'] ?? $data;
                if (is_array($docs)) {
                    foreach ($docs as $doc) {
                        if (is_array($doc) && isset($doc['url'])) {
                            return $doc['url'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("BoxtalShipping: exception récupération étiquette {$shippingOrderId}", [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function fetchTrackingV3(string $shippingOrderId): array
    {
        if (! config('shipping.boxtal.v3_access_key')) {
            return ['tracking_number' => null, 'tracking_url' => null, 'events' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth(),
                'Accept' => 'application/json',
            ])->get($this->baseUrl() . '/shipping/v3.1/shipping-order/' . $shippingOrderId . '/tracking');

            if (! $response->successful()) {
                Log::warning("BoxtalShipping: échec récupération tracking pour shipping_order={$shippingOrderId}", [
                    'status' => $response->status(),
                ]);

                return ['tracking_number' => null, 'tracking_url' => null, 'events' => []];
            }

            $data = $response->json();
            $parcel = $data['parcels'][0] ?? $data['content'][0] ?? null;

            return [
                'tracking_number' => $parcel['trackingNumber'] ?? $data['trackingNumber'] ?? null,
                'tracking_url' => $parcel['trackingUrl'] ?? $data['trackingUrl'] ?? null,
                'events' => $parcel['events'] ?? $data['events'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error("BoxtalShipping: exception récupération tracking v3 {$shippingOrderId}", [
                'error' => $e->getMessage(),
            ]);

            return ['tracking_number' => null, 'tracking_url' => null, 'events' => []];
        }
    }

    public static function carrierName(Order $order): ?string
    {
        return match (true) {
            $order->relay_network === 'MONR_NETWORK' => 'Mondial Relay',
            $order->relay_network === 'CHRP_NETWORK' => 'Chronopost',
            str_contains($order->shipping_key ?? '', 'colissimo') => 'Colissimo',
            default => null,
        };
    }

    private function formatApiError(?array $body, int $status): string
    {
        if (! $body || ! isset($body['errors'])) {
            $message = $body['message'] ?? $body['error'] ?? null;
            if ($message) {
                return "{$message} (HTTP {$status})";
            }
            return "Erreur API Boxtal (HTTP {$status}) — " . json_encode($body, JSON_UNESCAPED_UNICODE);
        }

        $messages = [];
        foreach ($body['errors'] as $error) {
            $params = $error['parameters'] ?? [];

            // Boxtal renvoie parameters comme objet {field, code, value} ou comme tableau d'objets
            if (isset($params['field'])) {
                // Objet unique
                $field = $params['field'] ?? '';
                $value = $params['value'] ?? $params['code'] ?? '';
                $msg = ($error['code'] ?? 'Erreur') . " — {$field}: {$value}";
            } elseif (is_array($params) && ! empty($params)) {
                // Tableau d'objets
                $details = [];
                foreach ($params as $param) {
                    if (is_array($param)) {
                        $field = $param['field'] ?? $param['name'] ?? '?';
                        $value = $param['value'] ?? $param['code'] ?? $param['message'] ?? '?';
                        $details[] = "{$field}: {$value}";
                    }
                }
                $msg = ($error['code'] ?? 'Erreur') . ($details ? ' — ' . implode(', ', $details) : '');
            } else {
                $msg = $error['code'] ?? $error['message'] ?? 'Erreur inconnue';
            }

            $messages[] = $msg;
        }

        return implode('; ', $messages) ?: "Erreur API Boxtal (HTTP {$status}) — " . json_encode($body, JSON_UNESCAPED_UNICODE);
    }
}
