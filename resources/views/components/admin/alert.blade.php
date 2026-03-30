@php
    $colors = [
        'success' => 'border-green-500 bg-green-50 text-green-700',
        'error' => 'border-red-500 bg-red-50 text-red-700',
        'warning' => 'border-yellow-500 bg-yellow-50 text-yellow-700',
        'info' => 'border-brand-500 bg-brand-50 text-brand-700',
    ];

    $alerts = [];
    if (session('success')) $alerts[] = ['type' => 'success', 'message' => session('success')];
    if (session('error')) $alerts[] = ['type' => 'error', 'message' => session('error')];
    if (session('warning')) $alerts[] = ['type' => 'warning', 'message' => session('warning')];
    if (session('info')) $alerts[] = ['type' => 'info', 'message' => session('info')];
@endphp

@foreach($alerts as $alert)
<div x-data="{ show: true }" x-show="show" x-transition
    class="mb-4 flex items-center justify-between rounded-lg border-l-4 p-4 {{ $colors[$alert['type']] }}">
    <p class="text-sm font-medium">{{ $alert['message'] }}</p>
    <button @click="show = false" class="ml-4 opacity-70 hover:opacity-100">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>
@endforeach
