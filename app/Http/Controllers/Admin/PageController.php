<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('sort_order')->orderBy('title')->get();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePage($request);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

        Page::create($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page créée.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $this->validatePage($request, $page);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

        $page->update($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page mise à jour.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page supprimée.');
    }

    private function validatePage(Request $request, ?Page $page = null): array
    {
        $uniqueSlug = $page ? ",{$page->id}" : '';

        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => "nullable|string|max:255|unique:pages,slug{$uniqueSlug}|regex:/^[a-z0-9-]+$/",
            'content' => 'nullable|string|max:50000',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
