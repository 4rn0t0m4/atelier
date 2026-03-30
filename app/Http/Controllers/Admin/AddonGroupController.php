<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAddon;
use App\Models\ProductAddonGroup;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class AddonGroupController extends Controller
{
    public function index()
    {
        $groups = ProductAddonGroup::withCount(['addons', 'products'])
            ->orderBy('is_global', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.addon-groups.index', compact('groups'));
    }

    public function create()
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.addon-groups.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_global' => 'boolean',
            'restrict_to_categories' => 'nullable|array',
            'restrict_to_categories.*' => 'exists:product_categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_global'] = $request->boolean('is_global');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        if (! $validated['is_global']) {
            $validated['restrict_to_categories'] = null;
        }

        $group = ProductAddonGroup::create($validated);

        $this->syncAddons($group, $request->input('addons', []));

        return redirect()->route('admin.addon-groups.edit', $group)->with('success', 'Groupe d\'options créé.');
    }

    public function edit(ProductAddonGroup $addonGroup)
    {
        $addonGroup->load('addons');
        $categories = ProductCategory::orderBy('name')->get();

        return view('admin.addon-groups.edit', compact('addonGroup', 'categories'));
    }

    public function update(Request $request, ProductAddonGroup $addonGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_global' => 'boolean',
            'restrict_to_categories' => 'nullable|array',
            'restrict_to_categories.*' => 'exists:product_categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_global'] = $request->boolean('is_global');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        if (! $validated['is_global']) {
            $validated['restrict_to_categories'] = null;
        }

        $addonGroup->update($validated);

        $this->syncAddons($addonGroup, $request->input('addons', []));

        return redirect()->route('admin.addon-groups.edit', $addonGroup)->with('success', 'Groupe d\'options mis à jour.');
    }

    public function destroy(ProductAddonGroup $addonGroup)
    {
        $addonGroup->addons()->delete();
        $addonGroup->delete();

        return redirect()->route('admin.addon-groups.index')->with('success', 'Groupe d\'options supprimé.');
    }

    public function duplicate(ProductAddonGroup $addonGroup)
    {
        $newGroup = $addonGroup->replicate();
        $newGroup->name = $addonGroup->name . ' (copie)';
        $newGroup->save();

        foreach ($addonGroup->addons as $addon) {
            $newAddon = $addon->replicate();
            $newAddon->group_id = $newGroup->id;
            $newAddon->save();
        }

        return redirect()->route('admin.addon-groups.edit', $newGroup)->with('success', 'Groupe dupliqué.');
    }

    private function syncAddons(ProductAddonGroup $group, array $addonsData): void
    {
        $existingIds = $group->addons()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($addonsData as $index => $addonData) {
            if (empty($addonData['label'])) {
                continue;
            }

            $options = $this->parseOptions($addonData['options'] ?? []);

            $fields = [
                'label' => $addonData['label'],
                'type' => $addonData['type'] ?? 'text',
                'display' => $addonData['display'] ?? null,
                'price' => $addonData['price'] ?? 0,
                'price_type' => $addonData['price_type'] ?? 'flat_fee',
                'adjust_price' => ! empty($addonData['adjust_price']),
                'required' => ! empty($addonData['required']),
                'min' => $addonData['min'] ?? null,
                'max' => $addonData['max'] ?? null,
                'description' => $addonData['description'] ?? null,
                'placeholder' => $addonData['placeholder'] ?? null,
                'options' => $options,
                'sort_order' => $index,
            ];

            if (! empty($addonData['id']) && in_array($addonData['id'], $existingIds)) {
                $addon = ProductAddon::find($addonData['id']);
                $addon->update($fields);
                $keepIds[] = $addon->id;
            } else {
                $fields['group_id'] = $group->id;
                $addon = ProductAddon::create($fields);
                $keepIds[] = $addon->id;
            }
        }

        // Supprimer les addons qui ne sont plus dans le formulaire
        ProductAddon::where('group_id', $group->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    private function parseOptions(array $optionsData): ?array
    {
        $options = [];
        foreach ($optionsData as $opt) {
            if (empty($opt['label'])) {
                continue;
            }
            $options[] = [
                'label' => $opt['label'],
                'price' => (float) ($opt['price'] ?? 0),
                'price_type' => $opt['price_type'] ?? 'flat_fee',
            ];
        }

        return $options ?: null;
    }
}
