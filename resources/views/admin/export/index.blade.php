@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Export" :breadcrumbs="['Export' => null]" />

    <div class="max-w-xl">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Export des ventes mensuelles</h3>

            <form method="GET" action="{{ route('admin.export.orders') }}" class="space-y-4" data-turbo="false">
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                    <input type="month" id="month" name="month"
                           value="{{ now()->format('Y-m') }}"
                           max="{{ now()->format('Y-m') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('month')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Télécharger l'export ODS
                </button>
            </form>
        </div>
    </div>
@endsection
