<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('excluded_global_group_ids')->nullable()->after('exclude_global_addons');
        });

        // Migrer les données : pour les produits avec exclude_global_addons=true,
        // on exclut tous les groupes globaux qui s'appliquaient à leur catégorie
        $globalGroups = DB::table('product_addon_groups')->where('is_global', true)->get();
        $products = DB::table('products')->where('exclude_global_addons', true)->get();

        foreach ($products as $product) {
            $excludedIds = $globalGroups->filter(function ($g) use ($product) {
                $cats = json_decode($g->restrict_to_categories, true);
                if (empty($cats)) return true;
                return in_array($product->category_id, $cats);
            })->pluck('id')->values()->toArray();

            if (! empty($excludedIds)) {
                DB::table('products')->where('id', $product->id)->update([
                    'excluded_global_group_ids' => json_encode($excludedIds),
                ]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('exclude_global_addons');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('exclude_global_addons')->default(false)->after('is_featured');
        });

        // Restaurer : si excluded_global_group_ids non vide → exclude_global_addons = true
        DB::table('products')
            ->whereNotNull('excluded_global_group_ids')
            ->update(['exclude_global_addons' => true]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('excluded_global_group_ids');
        });
    }
};
