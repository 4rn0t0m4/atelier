@php
    $notice = \App\Models\Setting::whereIn('key', [
        'closure_notice_enabled',
        'closure_notice_message',
        'closure_notice_until',
    ])->pluck('value', 'key');

    $noticeUntil = $notice['closure_notice_until'] ?? null;
    $showNotice = ($notice['closure_notice_enabled'] ?? '0') === '1'
        && filled($notice['closure_notice_message'] ?? null)
        && (blank($noticeUntil) || today()->lessThanOrEqualTo(\Illuminate\Support\Carbon::parse($noticeUntil)));
@endphp

@if($showNotice)
    <div class="bg-brand-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-center gap-2.5 text-center">
            <svg class="w-4 h-4 shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm">{{ $notice['closure_notice_message'] }}</p>
        </div>
    </div>
@endif
