@props(['status'])

@php
    $styles = [
        'pending' => 'bg-yellow-50 text-yellow-700',
        'processing' => 'bg-brand-50 text-brand-700',
        'shipped' => 'bg-blue-50 text-blue-700',
        'completed' => 'bg-green-50 text-green-700',
        'cancelled' => 'bg-red-50 text-red-700',
        'refunded' => 'bg-gray-100 text-gray-600',
        'failed' => 'bg-red-50 text-red-700',
        'on-hold' => 'bg-orange-50 text-orange-700',
        'published' => 'bg-green-50 text-green-700',
        'draft' => 'bg-gray-100 text-gray-600',
    ];

    $labels = [
        'pending' => 'Non reglée',
        'processing' => 'En cours',
        'shipped' => 'Expédiée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'refunded' => 'Remboursée',
        'failed' => 'Échouée',
        'on-hold' => 'En attente',
        'published' => 'Publiée',
        'draft' => 'Brouillon',
    ];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $styles[$status] ?? $styles['pending'] }}">
    {{ $labels[$status] ?? $status }}
</span>
