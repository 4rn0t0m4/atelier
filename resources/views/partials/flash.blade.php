@if(session('success') || session('error'))
<div id="flash-messages"
     x-data="{ show: true }"
     x-init="setTimeout(() => show = false, 4000)"
     x-show="show"
     x-transition.opacity
     class="fixed bottom-6 right-6 z-50 max-w-sm">
    @if(session('success'))
        <div class="bg-brand-700 text-white text-sm px-4 py-3 rounded shadow-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-600 text-white text-sm px-4 py-3 rounded shadow-lg">
            {{ session('error') }}
        </div>
    @endif
</div>
@endif
