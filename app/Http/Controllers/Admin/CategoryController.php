<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::with(['parent', 'featuredImage'])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Grouper : parents d'abord, puis enfants récursivement
        $grouped = collect();
        $addChildren = function ($parentId, $depth = 0) use (&$addChildren, $categories, $grouped) {
            $items = $categories->where('parent_id', $parentId ?: null)->sortBy('sort_order');
            foreach ($items as $item) {
                $item->depth = $depth;
                $grouped->push($item);
                $addChildren($item->id, $depth + 1);
            }
        };
        $addChildren(null);

        return view('admin.categories.index', ['categories' => $grouped]);
    }

    public function create()
    {
        $parents = ProductCategory::root()->orderBy('name')->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug|regex:/^[a-z0-9-]+$/',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ProductCategory::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(ProductCategory $category)
    {
        $parents = ProductCategory::root()
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => "nullable|string|max:255|unique:product_categories,slug,{$category->id}|regex:/^[a-z0-9-]+$/",
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(ProductCategory $category)
    {
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Impossible de supprimer une catégorie contenant des produits.');
        }

        if ($category->children()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Impossible de supprimer une catégorie contenant des sous-catégories.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Catégorie supprimée.');
    }
}
