<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30)->unique();
            $table->string('status', 30)->default('pending'); // pending, processing, completed, cancelled, refunded

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 5)->default('EUR');

            $table->string('payment_method', 30)->nullable(); // stripe, paypal
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('paypal_order_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Billing
            $table->string('billing_first_name', 100)->nullable();
            $table->string('billing_last_name', 100)->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone', 30)->nullable();
            $table->string('billing_address_1')->nullable();
            $table->string('billing_address_2')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_postcode', 20)->nullable();
            $table->string('billing_country', 5)->nullable();

            // Shipping
            $table->string('shipping_first_name', 100)->nullable();
            $table->string('shipping_last_name', 100)->nullable();
            $table->string('shipping_address_1')->nullable();
            $table->string('shipping_address_2')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_postcode', 20)->nullable();
            $table->string('shipping_country', 5)->nullable();

            $table->string('shipping_method', 50)->nullable();
            $table->string('shipping_key', 50)->nullable();

            // Point relais
            $table->string('relay_point_code', 50)->nullable();
            $table->string('relay_network', 50)->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('tracking_carrier', 50)->nullable();
            $table->string('tracking_url')->nullable();

            // Boxtal
            $table->string('boxtal_shipping_order_id')->nullable();
            $table->string('boxtal_label_url')->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('review_requested_at')->nullable();

            $table->text('customer_note')->nullable();
            $table->string('coupon_code', 50)->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('stripe_payment_intent_id');
            $table->index('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
