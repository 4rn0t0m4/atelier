@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Avis clients" :breadcrumbs="['Boutique' => '', 'Avis' => '']" />

<div class="p-6">
    <x-admin.alert />

    {{-- Filtres --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('admin.reviews.index') }}"
           class="px-3 py-1.5 text-sm rounded-lg transition {{ !request('status') ? 'bg-brand-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            Tous
        </a>
        <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}"
           class="px-3 py-1.5 text-sm rounded-lg transition inline-flex items-center gap-1.5 {{ request('status') === 'pending' ? 'bg-brand-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            En attente
            @if($pendingCount > 0)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full {{ request('status') === 'pending' ? 'bg-white text-brand-700' : 'bg-amber-100 text-amber-700' }}">{{ $pendingCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}"
           class="px-3 py-1.5 text-sm rounded-lg transition {{ request('status') === 'approved' ? 'bg-brand-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            Approuvés
        </a>

        <form action="{{ route('admin.reviews.index') }}" method="GET" class="ml-auto flex gap-2">
            @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher…"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48 focus:ring-brand-500 focus:border-brand-500">
            <button type="submit" class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 transition">Filtrer</button>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Produit</th>
                    <th class="px-6 py-3 text-left">Auteur</th>
                    <th class="px-6 py-3 text-left">Avis</th>
                    <th class="px-6 py-3 text-center">Note</th>
                    <th class="px-6 py-3 text-center">Statut</th>
                    <th class="px-6 py-3 text-center">Date</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reviews as $review)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        @if($review->product)
                            <a href="{{ $review->product->url() }}" target="_blank" class="text-brand-700 hover:underline font-medium">
                                {{ Str::limit($review->product->name, 30) }}
                            </a>
                        @else
                            <span class="text-gray-400 italic">Produit supprimé</span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <div class="font-medium text-gray-900">{{ $review->author_name }}</div>
                        @if($review->author_email)
                            <div class="text-xs text-gray-400">{{ $review->author_email }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-gray-600 max-w-xs">
                        {{ Str::limit($review->content, 80) }}
                    </td>
                    <td class="px-6 py-3 text-center">
                        <div class="flex items-center justify-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    </td>
                    <td class="px-6 py-3 text-center">
                        @if($review->is_approved)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Approuvé
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-amber-50 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> En attente
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center text-gray-500 text-xs">
                        {{ $review->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        @if(! $review->is_approved)
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-green-600 hover:text-green-800 mr-2" title="Approuver">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-amber-500 hover:text-amber-700 mr-2" title="Rejeter">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cet avis ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700" title="Supprimer">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">Aucun avis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
    <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection
