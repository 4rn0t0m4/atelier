<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('ai_personalization')->default(false)->after('light_shipping');
            $table->decimal('ai_supplement_price', 10, 2)->nullable()->after('ai_personalization');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['ai_personalization', 'ai_supplement_price']);
        });
    }
};
