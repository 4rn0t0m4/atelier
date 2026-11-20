<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Notifications push vers les appareils des admins (app installee ou navigateur).
 *
 * Aucune methode ne leve d'exception : un echec d'envoi ne doit jamais
 * interrompre la confirmation d'une commande.
 */
class AdminPushService
{
    public function isConfigured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'));
    }

    /**
     * Previent tous les admins qu'une commande vient d'etre payee.
     */
    public function notifyNewOrder(Order $order): int
    {
        try {
            return $this->send(
                PushSubscription::forAdmins()->get(),
                $this->newOrderPayload($order),
                // Le meme topic remplace une notification encore en attente de remise
                ['topic' => 'order-' . $order->id],
            );
        } catch (\Throwable $e) {
            Log::error("Erreur notification push commande #{$order->number}", ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Envoie une notification de test aux appareils d'un admin.
     */
    public function sendTest(User $user): int
    {
        try {
            return $this->send(
                PushSubscription::where('user_id', $user->id)->get(),
                [
                    'title' => 'Notifications activées',
                    'body' => 'Vous serez prévenu ici à chaque nouvelle commande.',
                    'url' => route('admin.orders.index', [], false),
                    'tag' => 'test',
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Erreur notification push de test', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Signale a tous les admins une connexion a un compte administrateur.
     */
    public function notifyAdminLogin(User $user, string $device, string $ip): int
    {
        try {
            return $this->send(
                PushSubscription::forAdmins()->get(),
                [
                    'title' => "Connexion à l'admin",
                    'body' => "{$user->email}\n{$device} · IP {$ip}\nCe n'est pas vous ? Changez votre mot de passe.",
                    'url' => route('admin.security.index', [], false),
                    'tag' => 'admin-login',
                    // Le son de l'app est reserve aux ventes (voir admin-sw.js)
                    'silent' => true,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('Erreur notification push de connexion admin', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    public function newOrderPayload(Order $order): array
    {
        $order->loadMissing('items');

        $lines = $order->items->map(fn ($item) => "{$item->quantity} × {$item->product_name}");
        $summary = $lines->take(2)->implode(', ');
        if ($lines->count() > 2) {
            $summary .= ' (+' . ($lines->count() - 2) . ')';
        }

        return [
            'title' => 'Nouvelle commande · ' . number_format((float) $order->total, 2, ',', ' ') . ' €',
            'body' => implode("\n", array_filter([
                trim($order->billing_full_name . ' · ' . $order->number, ' ·'),
                $summary,
                $order->shipping_method,
            ])),
            // La liste ouvre le panneau de detail, plus lisible sur telephone que la page complete
            'url' => route('admin.orders.index', ['commande' => $order->id], false),
            'tag' => 'order-' . $order->id,
            'badgeCount' => Order::where('status', 'processing')->count(),
        ];
    }

    /**
     * @param  iterable<PushSubscription>  $subscriptions
     * @return int nombre d'appareils joints
     */
    protected function send(iterable $subscriptions, array $payload, array $options = []): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $webPush = app(WebPush::class);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $queued = 0;

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->public_key,
                        'auth' => $subscription->auth_token,
                    ],
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $json,
                $options,
            );
            $queued++;
        }

        if ($queued === 0) {
            return 0;
        }

        $delivered = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $delivered++;

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                // Appareil desabonne ou app desinstallee : l'abonnement ne reviendra pas
                PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($report->getEndpoint()))->delete();

                continue;
            }

            Log::warning('Notification push non remise', ['reason' => $report->getReason()]);
        }

        return $delivered;
    }
}
