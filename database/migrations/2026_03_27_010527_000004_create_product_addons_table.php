<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('product_addon_groups')->cascadeOnDelete();
            $table->string('label');
            $table->string('type', 30); // text, textarea, select, radio, checkbox
            $table->string('display', 30)->nullable(); // dropdown, radiobutton, images
            $table->decimal('price', 10, 2)->default(0);
            $table->string('price_type', 30)->default('flat_fee'); // flat_fee, quantity_based, percentage_based
            $table->boolean('adjust_price')->default(false);
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('min')->nullable();
            $table->unsignedSmallInteger('max')->nullable();
            $table->string('restrictions_type', 30)->nullable();
            $table->string('description')->nullable();
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable(); // [{label, price, price_type, image_id}]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addons');
    }
};
