<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('coupon_code', 50)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->string('type', 30)->default('coupon'); // coupon, automatic
            $table->string('discount_type', 30)->default('percentage'); // percentage, fixed_cart
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->json('target_categories')->nullable();
            $table->json('target_products')->nullable();
            $table->decimal('min_cart_value', 10, 2)->nullable();
            $table->decimal('max_cart_value', 10, 2)->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('stackable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('free_shipping')->default(false);
            $table->boolean('exclude_sale_items')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
