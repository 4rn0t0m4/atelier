<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\AdminPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PushSubscriptionController extends Controller
{
    /**
     * Enregistre (ou rafraichit) l'abonnement push de l'appareil courant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|url|starts_with:https://|max:1000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'contentEncoding' => 'nullable|string|in:aes128gcm,aesgcm',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($validated['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ],
        );

        return response()->json(['success' => 'Notifications activées sur cet appareil.']);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate(['endpoint' => 'required|string|max:1000']);

        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($validated['endpoint']))->delete();

        return response()->json(['success' => 'Notifications désactivées sur cet appareil.']);
    }

    public function test(Request $request, AdminPushService $push)
    {
        if (! $push->isConfigured()) {
            return response()->json(['error' => 'Clés VAPID absentes du fichier .env.'], 503);
        }

        $delivered = $push->sendTest($request->user());

        return $delivered > 0
            ? response()->json(['success' => 'Notification de test envoyée.'])
            : response()->json(['error' => "L'envoi a échoué, consultez les logs."], 502);
    }
}
