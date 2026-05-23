<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $tags = ProductTag::withCount('products')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:product_tags,slug'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        ProductTag::create($validated);

        return redirect()->route('admin.tags.index')->with('success', 'Tag créé.');
    }

    public function edit(ProductTag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, ProductTag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:product_tags,slug,' . $tag->id],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);

        return redirect()->route('admin.tags.index')->with('success', 'Tag mis à jour.');
    }

    public function destroy(ProductTag $tag)
    {
        $tag->products()->detach();
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', 'Tag supprimé.');
    }
}
