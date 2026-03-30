@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Catégories" :breadcrumbs="['Catalogue' => '', 'Catégories' => '']" />

<div class="p-6">
    <x-admin.alert />

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">{{ $categories->count() }} catégorie(s)</p>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
            + Nouvelle catégorie
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Nom</th>
                    <th class="px-6 py-3 text-left">Slug</th>
                    <th class="px-6 py-3 text-center">Produits</th>
                    <th class="px-6 py-3 text-center">Ordre</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        @if($category->parent_id)
                            <span class="text-gray-400 mr-1">└─</span>
                        @endif
                        <a href="{{ route('admin.categories.edit', $category) }}" class="font-medium text-gray-900 hover:text-brand-700">
                            {{ $category->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $category->slug }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $category->products_count }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $category->sort_order }}</td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ $category->url() }}" target="_blank" class="text-gray-400 hover:text-gray-600 mr-2" title="Voir">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-brand-600 hover:text-brand-800 mr-2">Modifier</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Aucune catégorie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
