@props(['title', 'breadcrumbs' => []])

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h2 class="text-xl font-semibold text-gray-800">
        {{ $title }}
    </h2>

    @if (count($breadcrumbs) > 0)
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500">
                        Accueil
                    </a>
                </li>
                @foreach ($breadcrumbs as $label => $url)
                    <li class="text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </li>
                    <li>
                        @if ($url)
                            <a href="{{ $url }}" class="text-sm text-gray-500 hover:text-brand-600">
                                {{ $label }}
                            </a>
                        @else
                            <span class="text-sm text-brand-600">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif
</div>
