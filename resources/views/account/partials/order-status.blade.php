@php
$map = [
    'pending'    => ['Non réglé', 'bg-yellow-50 text-yellow-700'],
    'processing' => ['En cours', 'bg-blue-50 text-blue-700'],
    'on-hold'    => ['En attente', 'bg-orange-50 text-orange-700'],
    'completed'  => ['Complétée', 'bg-green-50 text-green-700'],
    'shipped'    => ['Expédiée', 'bg-indigo-50 text-indigo-700'],
    'cancelled'  => ['Annulée', 'bg-gray-100 text-gray-500'],
    'refunded'   => ['Remboursée', 'bg-purple-50 text-purple-700'],
    'failed'     => ['Échouée', 'bg-red-50 text-red-700'],
];
[$label, $class] = $map[$status] ?? ['Inconnu', 'bg-gray-100 text-gray-500'];
@endphp
<span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $class }}">{{ $label }}</span>
