@props(['addon'])

@if($addon->type === 'heading')
<div class="pt-3 border-t border-gray-200 mt-3">
    <p class="text-sm font-semibold text-gray-800 uppercase tracking-wide">{{ $addon->label }}</p>
    @if($addon->description)
        <p class="text-xs text-gray-400">{{ $addon->description }}</p>
    @endif
</div>
@else
<div class="space-y-1">
    <label class="text-base font-medium text-gray-700">
        {{ $addon->label }}
        @if($addon->required) <span class="text-red-500">*</span> @endif
        @if($addon->adjust_price && $addon->price > 0 && ! in_array($addon->type, ['select', 'radio', 'checkbox']))
            <span class="text-xs text-gray-400 font-normal">
                (+{{ number_format($addon->price, 2, ',', ' ') }}
                {{ $addon->price_type === 'percentage_based' ? '%' : '€' }}@if($addon->price_type === 'flat_fee') / commande @endif)
            </span>
        @endif
    </label>

    @if($addon->description)
        <p class="text-xs text-gray-400">{{ $addon->description }}</p>
    @endif

    @switch($addon->type)
        @case('heading')
            {{-- Juste un sous-titre visuel, pas de champ de saisie --}}
            @break

        @case('text')
            <input type="text"
                   name="addons[{{ $addon->id }}][value]"
                   placeholder="{{ $addon->placeholder }}"
                   {{ $addon->required ? 'required' : '' }}
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            @break

        @case('textarea')
            <textarea name="addons[{{ $addon->id }}][value]"
                      rows="3"
                      placeholder="{{ $addon->placeholder }}"
                      {{ $addon->required ? 'required' : '' }}
                      @if($addon->sync_qty)
                      x-on:input="
                          let lines = $el.value.split('\n').filter(l => l.trim() !== '');
                          qty = Math.max(1, lines.length);
                      "
                      @endif
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            @if($addon->sync_qty)
                <p class="text-xs text-gray-400">Un prénom par ligne — la quantité s'ajuste automatiquement.</p>
            @endif
            @break

        @case('select')
            <select name="addons[{{ $addon->id }}][value]"
                    {{ $addon->required ? 'required' : '' }}
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">— Choisir —</option>
                @foreach($addon->options ?? [] as $i => $option)
                    <option value="{{ $option['label'] ?? $option }}">
                        {{ $option['label'] ?? $option }}
                        @if(!empty($option['price']) && $option['price'] > 0)
                            (+{{ number_format($option['price'], 2, ',', ' ') }} €)
                        @endif
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="addons[{{ $addon->id }}][option_index]" value="">
            @break

        @case('radio')
            <div class="flex flex-wrap gap-3">
                @foreach($addon->options ?? [] as $i => $option)
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio"
                               name="addons[{{ $addon->id }}][value]"
                               value="{{ $option['label'] ?? $option }}"
                               {{ $addon->required ? 'required' : '' }}>
                        {{ $option['label'] ?? $option }}
                        @if(!empty($option['price']) && $option['price'] > 0)
                            <span class="text-xs text-gray-400">(+{{ number_format($option['price'], 2, ',', ' ') }} €)</span>
                        @endif
                    </label>
                @endforeach
            </div>
            @break

        @case('checkbox')
            <div class="flex flex-wrap gap-3">
                @foreach($addon->options ?? [] as $i => $option)
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox"
                               name="addons[{{ $addon->id }}][]"
                               value="{{ $option['label'] ?? $option }}">
                        {{ $option['label'] ?? $option }}
                        @if(!empty($option['price']) && $option['price'] > 0)
                            <span class="text-xs text-gray-400">(+{{ number_format($option['price'], 2, ',', ' ') }} €)</span>
                        @endif
                    </label>
                @endforeach
            </div>
            @break

        @case('file')
            <input type="file"
                   name="addons[{{ $addon->id }}][file]"
                   {{ $addon->required ? 'required' : '' }}
                   class="text-sm text-gray-600">
            @break

        @default
            <input type="text"
                   name="addons[{{ $addon->id }}][value]"
                   placeholder="{{ $addon->placeholder }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    @endswitch

    <input type="hidden" name="addons[{{ $addon->id }}][label]" value="{{ $addon->label }}">
</div>
@endif
