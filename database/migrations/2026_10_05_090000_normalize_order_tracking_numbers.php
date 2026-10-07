<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Nettoie les numeros de suivi saisis avec des espaces (ex. « 8J 0090097889 8 »),
     * qui cassaient le lien de suivi La Poste genere a la volee.
     */
    public function up(): void
    {
        Order::where('tracking_number', 'like', '% %')
            ->get(['id', 'tracking_number'])
            ->each(function (Order $order) {
                $order->tracking_number = Order::normalizeTrackingNumber($order->tracking_number);
                $order->save();
            });
    }

    public function down(): void
    {
        // Irreversible : les espaces d'origine n'ont aucune valeur.
    }
};
