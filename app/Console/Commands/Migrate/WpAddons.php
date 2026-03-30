<?php

namespace App\Console\Commands\Migrate;

use App\Models\ProductAddon;
use App\Models\ProductAddonAssignment;
use App\Models\ProductAddonGroup;

class WpAddons extends WpImportCommand
{
    protected $signature = 'migrate:wp-addons';
    protected $description = 'Importe les groupes d\'addons et champs depuis WooCommerce Product Addons';

    /**
     * Types WC → types Laravel
     */
    private array $typeMap = [
        'custom_text' => 'text',
        'custom_textarea' => 'textarea',
        'multiple_choice' => 'select',   // display détermine le rendu (select/radio)
        'checkbox' => 'checkbox',
        'input_multiplier' => 'text',
        'custom_price' => 'text',
        'heading' => 'heading',
    ];

    public function handle(): int
    {
        $this->info('Import des addons WooCommerce...');

        $this->safeTruncate('product_addon_assignments');
        $this->safeTruncate('product_addons');
        $this->safeTruncate('product_addon_groups');

        $categoryMap = $this->loadMap('wp_category_map.json');
        $productMap = $this->loadMap('wp_product_map.json');

        $groupsCreated = 0;
        $addonsCreated = 0;
        $assignmentsCreated = 0;

        // --- 1. Groupes globaux (post_type = global_product_addon) ---
        $this->info('  Groupes globaux...');

        $globals = $this->wp()
            ->table('posts')
            ->where('post_type', 'global_product_addon')
            ->orderBy('ID')
            ->get();

        foreach ($globals as $gp) {
            $raw = $this->getMeta($gp->ID, '_product_addons');
            $fields = $raw ? @unserialize($raw) : [];

            if (! is_array($fields) || empty($fields)) {
                continue;
            }

            // Catégories liées
            $wpCatIds = $this->wp()
                ->table('term_relationships as tr')
                ->join('term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
                ->where('tr.object_id', $gp->ID)
                ->where('tt.taxonomy', 'product_cat')
                ->pluck('tt.term_id')
                ->toArray();

            $laravelCatIds = array_values(array_filter(
                array_map(fn ($id) => $categoryMap[(int) $id] ?? null, $wpCatIds)
            ));

            $group = ProductAddonGroup::create([
                'name' => $gp->post_title,
                'description' => '',
                'is_global' => true,
                'restrict_to_categories' => $laravelCatIds ?: null,
                'sort_order' => $groupsCreated,
            ]);
            $groupsCreated++;

            $addonsCreated += $this->importFields($group, $fields);
        }

        // --- 2. Addons par produit (postmeta _product_addons) ---
        $this->info('  Addons par produit...');

        $productAddons = $this->wp()
            ->table('postmeta')
            ->where('meta_key', '_product_addons')
            ->whereExists(function ($q) {
                $q->select($this->wp()->raw(1))
                    ->from('posts')
                    ->whereColumn('posts.ID', 'postmeta.post_id')
                    ->where('post_type', 'product');
            })
            ->get();

        foreach ($productAddons as $row) {
            $fields = @unserialize($row->meta_value);

            if (! is_array($fields) || empty($fields)) {
                continue;
            }

            $laravelProductId = $productMap[(int) $row->post_id] ?? null;
            if (! $laravelProductId) {
                continue;
            }

            // Récupérer le nom du produit pour nommer le groupe
            $productName = $this->wp()
                ->table('posts')
                ->where('ID', $row->post_id)
                ->value('post_title');

            $group = ProductAddonGroup::create([
                'name' => 'Options — ' . ($productName ?: "Produit #{$row->post_id}"),
                'description' => '',
                'is_global' => false,
                'restrict_to_categories' => null,
                'sort_order' => $groupsCreated,
            ]);
            $groupsCreated++;

            $addonsCreated += $this->importFields($group, $fields);

            // Assigner au produit
            ProductAddonAssignment::create([
                'group_id' => $group->id,
                'product_id' => $laravelProductId,
            ]);
            $assignmentsCreated++;

            // Importer le flag exclude_global : exclure tous les globaux applicables
            $excludeGlobal = $this->getMeta($row->post_id, '_product_addons_exclude_global');
            if ($excludeGlobal == '1') {
                $product = \App\Models\Product::find($laravelProductId);
                if ($product) {
                    $applicableGlobalIds = ProductAddonGroup::where('is_global', true)
                        ->get()
                        ->filter(function ($g) use ($product) {
                            if (empty($g->restrict_to_categories)) return true;
                            return in_array($product->category_id, $g->restrict_to_categories);
                        })
                        ->pluck('id')
                        ->values()
                        ->toArray();

                    if (! empty($applicableGlobalIds)) {
                        $product->update(['excluded_global_group_ids' => $applicableGlobalIds]);
                    }
                }
            }
        }

        $this->printResult('Groupes', $groupsCreated);
        $this->printResult('Champs addon', $addonsCreated);
        $this->printResult('Assignments produit', $assignmentsCreated);

        return self::SUCCESS;
    }

    private function importFields(ProductAddonGroup $group, array $fields): int
    {
        $count = 0;

        foreach ($fields as $i => $field) {
            $type = $this->typeMap[$field['type'] ?? ''] ?? 'text';

            // Pour multiple_choice, le display détermine le vrai type
            if (($field['type'] ?? '') === 'multiple_choice') {
                $type = match ($field['display'] ?? 'select') {
                    'radiobutton' => 'radio',
                    'images' => 'radio',
                    default => 'select',
                };
            }

            if (($field['type'] ?? '') === 'checkbox') {
                $type = 'checkbox';
            }

            // Options avec prix
            $options = [];
            foreach ($field['options'] ?? [] as $opt) {
                $options[] = [
                    'label' => $opt['label'] ?? '',
                    'price' => $opt['price'] !== '' ? (float) $opt['price'] : 0,
                    'price_type' => $opt['price_type'] ?? 'flat_fee',
                    'image' => $opt['image'] ?? '',
                ];
            }

            $label = $field['name'] ?? 'Option';

            // Sync quantité auto pour les textarea "Prénoms" (1 prénom par ligne)
            $syncQty = $type === 'textarea' && $label === 'Prénoms';

            ProductAddon::create([
                'group_id' => $group->id,
                'label' => $label,
                'type' => $type,
                'display' => $field['display'] ?? 'select',
                'price' => ! empty($field['price']) ? (float) $field['price'] : 0,
                'price_type' => $field['price_type'] ?? 'flat_fee',
                'adjust_price' => (bool) ($field['adjust_price'] ?? false),
                'required' => (bool) ($field['required'] ?? false),
                'sync_qty' => $syncQty,
                'min' => (int) ($field['min'] ?? 0),
                'max' => (int) ($field['max'] ?? 0),
                'restrictions_type' => $field['restrictions_type'] ?? null,
                'description' => $field['description'] ?? null,
                'placeholder' => $field['placeholder'] ?? null,
                'options' => $options ?: null,
                'sort_order' => $field['position'] ?? $i,
            ]);

            $count++;
        }

        return $count;
    }
}
