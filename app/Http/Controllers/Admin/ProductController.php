<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductAddonAssignment;
use App\Models\ProductAddonGroup;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sortable = ['name', 'stock_quantity', 'price', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir = $request->dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $products = $query->paginate(20)->withQueryString();
        $categories = ProductCategory::with('children.children.children')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = ProductCategory::with('children.children.children')->orderBy('sort_order')->orderBy('name')->get();
        $addonGroups = ProductAddonGroup::with('addons')->orderBy('sort_order')->get();

        return view('admin.products.create', compact('categories', 'addonGroups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:products,slug'],
            'short_description' => 'nullable|string|max:65000',
            'description' => 'nullable|string|max:65000',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'stock_status' => 'required|in:instock,outofstock,onbackorder',
            'weight' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'featured_image_id' => 'nullable|integer|exists:media,id',
            'gallery_image_ids' => 'nullable|json',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['manage_stock'] = $request->boolean('manage_stock');
        $validated['featured_image_id'] = $request->input('featured_image_id') ?: null;
        $validated['gallery_image_ids'] = json_decode($request->input('gallery_image_ids', '[]'), true) ?: null;

        $validated['excluded_global_group_ids'] = $this->computeExcludedGlobalIds($request);

        $product = Product::create($validated);

        $this->syncAddonGroups($product, $request->input('addon_groups', []));

        return redirect()->route('admin.products.index')->with('success', 'Produit cree avec succes.');
    }

    public function edit(Product $product)
    {
        $product->load(['addonGroups', 'featuredImage']);
        $categories = ProductCategory::with('children.children.children')->orderBy('sort_order')->orderBy('name')->get();
        $addonGroups = ProductAddonGroup::with('addons')->orderBy('sort_order')->get();

        return view('admin.products.edit', compact('product', 'categories', 'addonGroups'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:products,slug,' . $product->id],
            'short_description' => 'nullable|string|max:65000',
            'description' => 'nullable|string|max:65000',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'stock_quantity' => 'nullable|integer|min:0',
            'manage_stock' => 'boolean',
            'stock_status' => 'required|in:instock,outofstock,onbackorder',
            'weight' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'featured_image_id' => 'nullable|integer|exists:media,id',
            'gallery_image_ids' => 'nullable|json',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['manage_stock'] = $request->boolean('manage_stock');
        $validated['featured_image_id'] = $request->input('featured_image_id') ?: null;
        $validated['gallery_image_ids'] = json_decode($request->input('gallery_image_ids', '[]'), true) ?: null;

        $validated['excluded_global_group_ids'] = $this->computeExcludedGlobalIds($request);

        $product->update($validated);

        $this->syncAddonGroups($product, $request->input('addon_groups', []));

        return redirect()->route('admin.products.index')->with('success', 'Produit mis a jour.');
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        return response()->json(['is_active' => $product->is_active]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produit supprime.');
    }

    private function syncAddonGroups(Product $product, array $groupIds): void
    {
        $product->addonGroups()->sync(array_filter($groupIds));
    }

    private function computeExcludedGlobalIds(Request $request): ?array
    {
        $checkedGlobalIds = array_map('intval', $request->input('global_groups', []));
        $allGlobalIds = ProductAddonGroup::where('is_global', true)->pluck('id')->toArray();

        $excluded = array_values(array_diff($allGlobalIds, $checkedGlobalIds));

        return ! empty($excluded) ? $excluded : null;
    }
}
