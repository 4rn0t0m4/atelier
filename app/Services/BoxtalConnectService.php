<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BoxtalConnectService
{
    private string $baseUrl = 'https://api.boxtal.com';

    private function auth(): string
    {
        return base64_encode(
            config('shipping.boxtal.access_key') . ':' . config('shipping.boxtal.secret_key')
        );
    }

    /**
     * Envoie la commande à Boxtal Connect.
     */
    public function pushOrder(Order $order): void
    {
        if (! config('shipping.boxtal.access_key')) {
            Log::warning('BoxtalConnect: BOXTAL_ACCESS_KEY manquant, push ignoré.');

            return;
        }

        $payload = $this->buildPayload($order);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->auth(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/v2/orders", $payload);

            if ($response->successful()) {
                Log::info("BoxtalConnect: commande #{$order->number} envoyée.", [
                    'boxtal_id' => $response->json('id'),
                ]);
            } else {
                Log::error("BoxtalConnect: échec push commande #{$order->number}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $this->notifyAdmin($order, "Erreur API {$response->status()}");
            }
        } catch (\Throwable $e) {
            Log::error("BoxtalConnect: exception push commande #{$order->number}", [
                'error' => $e->getMessage(),
            ]);
            $this->notifyAdmin($order, $e->getMessage());
        }
    }

    private function notifyAdmin(Order $order, string $error): void
    {
        try {
            $adminEmail = config('mail.admin_address', config('mail.from.address'));
            Mail::raw(
                "Échec de synchronisation Boxtal pour la commande #{$order->number}.\n\nErreur : {$error}\n\nVeuillez vérifier manuellement dans le dashboard Boxtal Connect.",
                fn ($m) => $m->to($adminEmail)->subject("Boxtal — échec commande #{$order->number}")
            );
        } catch (\Throwable $e) {
            Log::error("BoxtalConnect: impossible de notifier l'admin", ['error' => $e->getMessage()]);
        }
    }

    private function buildPayload(Order $order): array
    {
        $payload = [
            'reference' => $order->number,
            'customer' => [
                'firstName' => $order->shipping_first_name ?: $order->billing_first_name,
                'lastName' => $order->shipping_last_name ?: $order->billing_last_name,
                'email' => $order->billing_email,
                'phone' => $order->billing_phone,
                'address' => [
                    'street' => trim(($order->shipping_address_1 ?: $order->billing_address_1) . ' ' . ($order->shipping_address_2 ?: $order->billing_address_2 ?? '')),
                    'city' => $order->shipping_city ?: $order->billing_city,
                    'zipCode' => $order->shipping_postcode ?: $order->billing_postcode,
                    'country' => $order->shipping_country ?: $order->billing_country ?: 'FR',
                ],
            ],
            'orderLines' => $order->items->map(fn ($item) => [
                'description' => $item->product_name,
                'quantity' => $item->quantity,
                'unitPrice' => (float) $item->unit_price,
            ])->values()->all(),
            'totalPrice' => (float) $order->total,
            'currency' => $order->currency ?? 'EUR',
        ];

        if ($order->relay_point_code) {
            $payload['shippingMethod'] = [
                'type' => 'parcelPoint',
                'parcelPoint' => [
                    'code' => $order->relay_point_code,
                    'network' => $order->relay_network ?? 'MONR_NETWORK',
                ],
            ];
        }

        return $payload;
    }
}
