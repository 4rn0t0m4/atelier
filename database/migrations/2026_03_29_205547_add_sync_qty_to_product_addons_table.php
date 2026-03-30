<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_addons', function (Blueprint $table) {
            $table->boolean('sync_qty')->default(false)->after('required');
        });

        // Activer sync_qty pour tous les addons "Prénoms" de type textarea
        DB::table('product_addons')
            ->where('label', 'Prénoms')
            ->where('type', 'textarea')
            ->update(['sync_qty' => true]);
    }

    public function down(): void
    {
        Schema::table('product_addons', function (Blueprint $table) {
            $table->dropColumn('sync_qty');
        });
    }
};
