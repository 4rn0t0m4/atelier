<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReshipment;
use App\Services\ReshipmentService;
use Illuminate\Http\Request;

/**
 * Demandes de reexpedition en relais (colis retourne a l'expediteur).
 */
class OrderReshipmentController extends Controller
{
    public function __construct(private ReshipmentService $reshipments) {}

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.50|max:500',
            'email' => 'required|email|max:255',
        ], [
            'amount.min' => 'Le montant doit être d\'au moins 0,50 €.',
        ]);

        $reshipment = $this->reshipments->create($order, (float) $validated['amount'], $validated['email']);

        $msg = $reshipment->email_sent_at
            ? 'Demande de réexpédition créée et lien envoyé à ' . $order->fresh()->billing_email . '.'
            : 'Demande de réexpédition créée, mais l\'email n\'a pas pu être envoyé. Copiez le lien ci-dessous.';

        return redirect()->route('admin.orders.show', $order)->with($reshipment->email_sent_at ? 'success' : 'error', $msg);
    }

    public function resend(Order $order, OrderReshipment $reshipment)
    {
        abort_if($reshipment->order_id !== $order->id, 404);
        abort_unless($reshipment->isPending(), 403);

        $sent = $this->reshipments->sendRequestEmail($reshipment);

        return redirect()->route('admin.orders.show', $order)->with(
            $sent ? 'success' : 'error',
            $sent ? 'Lien de réexpédition renvoyé à ' . $order->billing_email . '.' : 'L\'email n\'a pas pu être envoyé.'
        );
    }

    public function cancel(Order $order, OrderReshipment $reshipment)
    {
        abort_if($reshipment->order_id !== $order->id, 404);
        abort_unless($reshipment->isPending(), 403);

        $reshipment->update(['status' => OrderReshipment::STATUS_CANCELLED]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Demande de réexpédition annulée : le lien n\'est plus valide.');
    }
}
