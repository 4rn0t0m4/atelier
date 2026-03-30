@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Groupes d'options" :breadcrumbs="['Catalogue' => '', 'Options produit' => '']" />

<div class="p-6">
    <x-admin.alert />

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">{{ $groups->count() }} groupe(s)</p>
        <a href="{{ route('admin.addon-groups.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
            + Nouveau groupe
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Nom</th>
                    <th class="px-6 py-3 text-center">Portée</th>
                    <th class="px-6 py-3 text-center">Champs</th>
                    <th class="px-6 py-3 text-center">Produits</th>
                    <th class="px-6 py-3 text-center">Ordre</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($groups as $group)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.addon-groups.edit', $group) }}" class="font-medium text-gray-900 hover:text-brand-700">
                            {{ $group->name }}
                        </a>
                        @if($group->description)
                            <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($group->description, 60) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        @if($group->is_global)
                            @if($group->restrict_to_categories && count($group->restrict_to_categories) > 0)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-50 text-blue-700">
                                    Global ({{ count($group->restrict_to_categories) }} cat.)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">
                                    Global (tous)
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                Par produit
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $group->addons_count }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">
                        @if($group->is_global)
                            <span class="text-gray-400">—</span>
                        @else
                            {{ $group->products_count }}
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $group->sort_order }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.addon-groups.edit', $group) }}" class="text-brand-600 hover:text-brand-800 mr-2">Modifier</a>
                        <form action="{{ route('admin.addon-groups.duplicate', $group) }}" method="POST" class="inline mr-2">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600">Dupliquer</button>
                        </form>
                        <form action="{{ route('admin.addon-groups.destroy', $group) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce groupe et tous ses champs ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Aucun groupe d'options.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
