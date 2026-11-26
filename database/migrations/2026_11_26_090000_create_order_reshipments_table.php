<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_reshipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Lien public envoye au client : /reexpedition/{token}
            $table->string('token', 64)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending'); // pending, paid, cancelled

            $table->string('payment_method', 30)->nullable(); // stripe, paypal
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('paypal_order_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Nouveau point relais choisi par le client
            $table->string('relay_point_code', 50)->nullable();
            $table->string('relay_network', 50)->nullable();
            $table->string('relay_point_name')->nullable();
            $table->string('relay_point_address', 500)->nullable();

            // Trace de la premiere expedition (retournee)
            $table->string('previous_relay_point_code', 50)->nullable();
            $table->string('previous_boxtal_shipping_order_id')->nullable();
            $table->string('previous_tracking_number')->nullable();

            $table->timestamp('email_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_reshipments');
    }
};
