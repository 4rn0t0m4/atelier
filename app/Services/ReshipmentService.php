<?php

namespace App\Services;

use App\Mail\ReshipmentPaid;
use App\Mail\ReshipmentPaidAdmin;
use App\Mail\ReshipmentRequest;
use App\Models\Order;
use App\Models\OrderReshipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reexpedition en point relais d'une commande retournee (colis non retire).
 *
 * L'admin cree une demande ; le client recoit un lien, choisit un nouveau relais
 * et regle les frais. Une fois payee, la commande repasse « en cours » avec le
 * nouveau relais, prete pour une nouvelle etiquette Boxtal.
 */
class ReshipmentService
{
    /**
     * Montant propose par defaut : le tarif relais de la zone de livraison.
     */
    public function defaultAmount(Order $order): float
    {
        $country = $order->shipping_country ?: $order->billing_country ?: 'FR';
        $key = $country === 'FR' ? 'boxtal' : 'boxtal_intl';

        return (float) config("shipping.methods.{$key}.price", 5.00);
    }

    /**
     * Cree la demande (en annulant toute demande encore en attente) et envoie le lien au client.
     */
    public function create(Order $order, float $amount, ?string $email = null): OrderReshipment
    {
        if ($email && $email !== $order->billing_email) {
            $order->update(['billing_email' => $email]);
        }

        $reshipment = DB::transaction(function () use ($order, $amount) {
            OrderReshipment::where('order_id', $order->id)
                ->where('status', OrderReshipment::STATUS_PENDING)
                ->update(['status' => OrderReshipment::STATUS_CANCELLED]);

            return OrderReshipment::create([
                'order_id' => $order->id,
                'token' => OrderReshipment::generateToken(),
                'amount' => $amount,
                'status' => OrderReshipment::STATUS_PENDING,
                'previous_relay_point_code' => $order->relay_point_code,
                'previous_boxtal_shipping_order_id' => $order->boxtal_shipping_order_id,
                'previous_tracking_number' => $order->tracking_number,
            ]);
        });

        $this->sendRequestEmail($reshipment);

        return $reshipment;
    }

    /**
     * Envoie (ou renvoie) l'email contenant le lien de reexpedition.
     */
    public function sendRequestEmail(OrderReshipment $reshipment): bool
    {
        $reshipment->loadMissing('order');

        try {
            Mail::to($reshipment->order->billing_email)->send(new ReshipmentRequest($reshipment));
            $reshipment->update(['email_sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::error("Email réexpédition #{$reshipment->order->number} échoué", ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Enregistre le relais choisi par le client et le moyen de paiement retenu.
     */
    public function setRelayPoint(OrderReshipment $reshipment, array $relay, string $paymentMethod): void
    {
        $reshipment->update([
            'relay_point_code' => $relay['code'],
            'relay_network' => $relay['network'] ?? null,
            'relay_point_name' => $relay['name'] ?? null,
            'relay_point_address' => $relay['address'] ?? null,
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Confirme le paiement (pending → paid) et remet la commande en preparation
     * avec le nouveau relais. Idempotent grace au lock pessimiste.
     *
     * @return bool true si la demande vient d'etre confirmee, false si deja traitee
     */
    public function confirmPayment(OrderReshipment $reshipment): bool
    {
        $confirmed = DB::transaction(function () use ($reshipment) {
            $locked = OrderReshipment::where('id', $reshipment->id)
                ->where('status', OrderReshipment::STATUS_PENDING)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $locked->update([
                'status' => OrderReshipment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $this->applyToOrder($locked);

            return true;
        });

        if (! $confirmed) {
            return false;
        }

        $reshipment->refresh()->load('order.items');

        Log::info("Réexpédition commande #{$reshipment->order->number} réglée ({$reshipment->formatted_amount}).");

        try {
            Mail::to($reshipment->order->billing_email)->send(new ReshipmentPaid($reshipment));
            Mail::to(config('mail.admin_address'))->send(new ReshipmentPaidAdmin($reshipment));
        } catch (\Throwable $e) {
            Log::error("Erreur envoi email réexpédition #{$reshipment->order->number}", ['error' => $e->getMessage()]);
        }

        app(AdminPushService::class)->notifyReshipmentPaid($reshipment);

        return true;
    }

    /**
     * Remet la commande « en cours » avec le nouveau relais, sans etiquette ni suivi,
     * pour que l'admin puisse creer une nouvelle expedition Boxtal.
     */
    private function applyToOrder(OrderReshipment $reshipment): void
    {
        $order = Order::where('id', $reshipment->order_id)->lockForUpdate()->first();

        $country = $order->shipping_country ?: $order->billing_country ?: 'FR';
        $shippingKey = $country === 'FR' ? 'boxtal' : 'boxtal_intl';

        $note = 'Réexpédition du ' . now()->format('d/m/Y') . ' — Point relais : ' . $reshipment->relay_point_name;
        if ($reshipment->relay_point_address) {
            $note .= ' — ' . $reshipment->relay_point_address;
        }

        $order->update([
            'status' => 'processing',
            'shipping_key' => $shippingKey,
            'shipping_method' => config("shipping.methods.{$shippingKey}.label"),
            'relay_point_code' => $reshipment->relay_point_code,
            'relay_network' => $reshipment->relay_network,
            'boxtal_shipping_order_id' => null,
            'boxtal_label_url' => null,
            'tracking_number' => null,
            'tracking_carrier' => null,
            'tracking_url' => null,
            'shipped_at' => null,
            'customer_note' => trim(($order->customer_note ? $order->customer_note . "\n\n" : '') . $note),
        ]);
    }
}
