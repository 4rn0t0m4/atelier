<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('product_id');
        });

        Schema::table('product_addon_assignments', function (Blueprint $table) {
            $table->index('product_id');
        });

        Schema::table('discount_rules', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('stock_notifications', function (Blueprint $table) {
            $table->index('notified');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });

        Schema::table('product_addon_assignments', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });

        Schema::table('discount_rules', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('stock_notifications', function (Blueprint $table) {
            $table->dropIndex(['notified']);
        });
    }
};
