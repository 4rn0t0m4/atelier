<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BoxtalSubscriptionController extends Controller
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

    public function index()
    {
        $subscriptions = [];
        $error = null;

        if (config('shipping.boxtal.v3_access_key')) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . $this->auth(),
                    'Accept' => 'application/json',
                ])->get($this->baseUrl() . '/shipping/v3.1/subscription');

                if ($response->successful()) {
                    $subscriptions = $response->json('content') ?? $response->json() ?? [];
                } else {
                    $error = 'Erreur API : ' . $response->status();
                }
            } catch (\Throwable $e) {
                $error = 'Connexion impossible : ' . $e->getMessage();
            }
        } else {
            $error = 'BOXTAL_V3_ACCESS_KEY non configurée.';
        }

        return view('admin.boxtal-subscriptions.index', compact('subscriptions', 'error'));
    }

    public function store(Request $request)
    {
        $callbackUrl = $request->input('callback_url', url('/api/boxtal/webhook'));
        $webhookSecret = $request->filled('webhook_secret')
            ? $request->input('webhook_secret')
            : bin2hex(random_bytes(32));
        $results = [];

        $eventTypes = $request->input('event_types')
            ? explode(',', $request->input('event_types'))
            : ['DOCUMENT_CREATED', 'TRACKING_CHANGED'];

        foreach ($eventTypes as $eventType) {
            $eventType = trim($eventType);
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Basic ' . $this->auth(),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post($this->baseUrl() . '/shipping/v3.1/subscription', [
                    'eventType' => $eventType,
                    'callbackUrl' => $callbackUrl,
                    'webhookSecret' => $webhookSecret,
                ]);

                if ($response->successful()) {
                    Setting::updateOrCreate(
                        ['key' => 'boxtal_v3_webhook_secret'],
                        ['value' => $webhookSecret]
                    );

                    $results[] = [
                        'eventType' => $eventType,
                        'success' => true,
                        'webhookSecret' => $webhookSecret,
                    ];

                    Log::info("BoxtalSubscription: souscription créée pour {$eventType}", [
                        'callback_url' => $callbackUrl,
                    ]);
                } else {
                    $results[] = [
                        'eventType' => $eventType,
                        'success' => false,
                        'error' => $response->status() . ' — ' . $response->body(),
                    ];
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'eventType' => $eventType,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return redirect()->route('admin.boxtal-subscriptions.index')
            ->with('results', $results);
    }

    public function destroy(string $id)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->auth(),
            ])->delete($this->baseUrl() . '/shipping/v3.1/subscription/' . $id);

            if ($response->successful()) {
                return redirect()->route('admin.boxtal-subscriptions.index')
                    ->with('success', "Souscription {$id} supprimée.");
            }

            return redirect()->route('admin.boxtal-subscriptions.index')
                ->with('error', 'Erreur : ' . $response->status() . ' — ' . $response->body());
        } catch (\Throwable $e) {
            return redirect()->route('admin.boxtal-subscriptions.index')
                ->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
