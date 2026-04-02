<?php

namespace App\Http\Controllers;

use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\Setting;
use App\Services\BoxtalShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BoxtalWebhookController extends Controller
{
    public function __construct(private BoxtalShippingService $shipping) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = config('shipping.boxtal.v3_webhook_secret')
            ?: Setting::where('key', 'boxtal_v3_webhook_secret')->value('value');

        if (! $secret) {
            Log::error('BoxtalWebhook: aucun secret configuré, webhook rejeté');

            return response()->json(['message' => 'Webhook secret not configured'], 500);
        }

        if (! $this->verifySignature($request, $secret)) {
            Log::warning('BoxtalWebhook: signature invalide');

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $eventType = $payload['type'] ?? $payload['eventType'] ?? null;

        Log::info('BoxtalWebhook: événement reçu', [
            'type' => $eventType ?? 'unknown',
            'shipping_order_id' => $payload['shippingOrderId'] ?? null,
        ]);

        return match ($eventType) {
            'TRACKING_CHANGED', 'tracking_changed' => $this->handleTrackingUpdate($payload),
            'DOCUMENT_CREATED', 'document_created' => $this->handleShippingDocument($payload),
            default => $this->handleUnknownEvent($payload),
        };
    }

    private function handleTrackingUpdate(array $payload): JsonResponse
    {
        $shippingOrderId = $payload['shippingOrderId'] ?? null;
        $shipmentExternalId = $payload['shipmentExternalId'] ?? null;

        if (! $shippingOrderId) {
            return response()->json(['message' => 'Missing shippingOrderId'], 200);
        }

        $order = Order::where('boxtal_shipping_order_id', $shippingOrderId)->first();

        if (! $order && $shipmentExternalId) {
            $order = Order::where('number', $shipmentExternalId)->first();
        }

        if (! $order) {
            Log::warning("BoxtalWebhook: commande introuvable pour shipping_order_id={$shippingOrderId}");

            return response()->json(['message' => 'Order not found'], 200);
        }

        $trackingNumber = null;
        $trackingUrl = null;
        $trackings = $payload['payload']['trackings'] ?? [];

        $isFinal = false;
        if (! empty($trackings)) {
            $firstTracking = $trackings[0];
            $trackingNumber = $firstTracking['trackingNumber'] ?? null;
            $trackingUrl = $firstTracking['packageTrackingUrl'] ?? null;
            $isFinal = $firstTracking['isFinal'] ?? false;
        }

        if (! $trackingNumber) {
            return response()->json(['message' => 'No tracking yet'], 200);
        }

        $carrier = BoxtalShippingService::carrierName($order);
        $wasAlreadyShipped = $order->status === 'shipped';
        $newStatus = $isFinal ? 'completed' : 'shipped';

        $order->update([
            'status' => $newStatus,
            'shipped_at' => $order->shipped_at ?? now(),
            'tracking_number' => $trackingNumber,
            'tracking_carrier' => $carrier ?? $order->tracking_carrier,
            'tracking_url' => $trackingUrl,
        ]);

        Log::info("BoxtalWebhook: commande #{$order->number} tracking mis à jour", [
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
        ]);

        if (! $wasAlreadyShipped) {
            try {
                $order->load('items');
                Mail::to($order->billing_email)->send(new OrderShipped($order));
                Mail::to(config('mail.admin_address'))->send(new OrderShipped($order));
            } catch (\Throwable $e) {
                Log::error("BoxtalWebhook: échec envoi email #{$order->number}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => 'OK'], 200);
    }

    private function handleShippingDocument(array $payload): JsonResponse
    {
        $shippingOrderId = $payload['shippingOrderId'] ?? null;

        if (! $shippingOrderId) {
            return response()->json(['message' => 'Missing shippingOrderId'], 200);
        }

        $order = Order::where('boxtal_shipping_order_id', $shippingOrderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 200);
        }

        $documents = $payload['payload']['documents'] ?? $payload['documents'] ?? [];
        $labelUrl = null;

        foreach ($documents as $doc) {
            if (isset($doc['url'])) {
                $labelUrl = $doc['url'];
                break;
            }
        }

        if ($labelUrl) {
            $order->update(['boxtal_label_url' => $labelUrl]);
            Log::info("BoxtalWebhook: étiquette enregistrée pour commande #{$order->number}");
        }

        return response()->json(['message' => 'OK'], 200);
    }

    private function handleUnknownEvent(array $payload): JsonResponse
    {
        Log::info('BoxtalWebhook: événement inconnu', ['payload' => $payload]);

        return response()->json(['message' => 'OK'], 200);
    }

    private function verifySignature(Request $request, string $secret): bool
    {
        $signature = $request->header('x-bxt-signature');

        if (! $signature) {
            return false;
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($computed, $signature);
    }
}
