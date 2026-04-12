@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Commandes" :breadcrumbs="['Commandes' => null]" />

    {{-- Metrics --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6 mb-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Commandes</div>
            <div class="mt-1 text-xl font-bold text-gray-800">{{ number_format($metrics['total_orders']) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">CA</div>
            <div class="mt-1 text-xl font-bold text-gray-800">{{ number_format($metrics['revenue'], 0, ',', ' ') }} &euro;</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Articles vendus</div>
            <div class="mt-1 text-xl font-bold text-gray-800">{{ number_format($metrics['items_sold']) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Panier moyen</div>
            <div class="mt-1 text-xl font-bold text-gray-800">{{ number_format($metrics['average_order'], 2, ',', ' ') }} &euro;</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-yellow-600 uppercase tracking-wide">Non reglées</div>
            <div class="mt-1 text-xl font-bold text-yellow-700">{{ $metrics['pending'] }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="text-xs text-brand-600 uppercase tracking-wide">En cours</div>
            <div class="mt-1 text-xl font-bold text-brand-700">{{ $metrics['processing'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl border border-gray-200 bg-white mb-6">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="p-4 md:p-5">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="N° commande, email, nom..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="w-40">
                    <label class="block text-xs text-gray-500 mb-1">Statut</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Tous</option>
                        @foreach(['pending' => 'Non reglée', 'processing' => 'En cours', 'shipped' => 'Expédiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée'] as $val => $lbl)
                            <option value="{{ $val }}" {{ (request('status') ?? (request()->hasAny(['search', 'status']) ? '' : 'processing')) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    Filtrer
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reinitialiser</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table + Panel --}}
    <div x-data="orderPanel()" @keydown.escape.window="close()">

        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-2 py-3 w-10"></th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Facture</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">N°</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Statut</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Livraison</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 cursor-pointer transition"
                                :class="activeOrderId === {{ $order->id }} && 'bg-brand-50 hover:bg-brand-50'"
                                @click="open({{ $order->id }})">
                                <td class="px-2 py-4" @click.stop>
                                    @if(in_array($order->status, ['pending', 'cancelled']))
                                        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST"
                                              onsubmit="return confirm('Supprimer la commande {{ $order->number }} ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-medium text-gray-700">{{ $order->invoice_number ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-medium text-brand-600">{{ $order->number }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">{{ $order->billing_first_name }} {{ $order->billing_last_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->billing_email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.badge :status="$order->status" />
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-500">
                                    {{ $order->shipping_method ?? '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-right font-medium text-gray-700">
                                    {{ number_format($order->total, 2, ',', ' ') }} &euro;
                                </td>
                                <td class="px-5 py-4 text-sm text-right text-gray-500">
                                    {{ $order->created_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">
                                    Aucune commande trouvée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="px-5 py-4 border-t border-gray-200">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

        {{-- Slide-over panel --}}
        <div x-show="open_" x-cloak class="fixed inset-0 z-40" style="display:none">
            {{-- Overlay --}}
            <div x-show="open_"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="close()"
                 class="absolute inset-0 bg-black/20"></div>

            {{-- Panel --}}
            <div x-show="open_"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="absolute right-0 top-0 bottom-0 w-full max-w-2xl bg-gray-50 shadow-2xl border-l border-gray-200 flex flex-col">

                {{-- Panel header --}}
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-white shrink-0">
                    <h2 class="text-sm font-semibold text-gray-800">Détail commande</h2>
                    <button @click="close()" class="p-1 rounded-lg hover:bg-gray-100 transition text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Panel body --}}
                <div class="flex-1 overflow-y-auto p-5" id="panel-body">
                    <template x-if="loading">
                        <div class="flex items-center justify-center py-20">
                            <svg class="animate-spin h-6 w-6 text-brand-600" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                    </template>
                    <div x-show="!loading" x-html="content"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function orderPanel() {
        return {
            open_: false,
            loading: false,
            content: '',
            activeOrderId: null,
            panelFlash: null,
            panelFlashType: 'success',

            open(orderId) {
                this.activeOrderId = orderId;
                this.open_ = true;
                this.loading = true;
                this.panelFlash = null;
                document.body.style.overflow = 'hidden';

                this.loadOrder(orderId);
            },

            close() {
                this.open_ = false;
                this.activeOrderId = null;
                document.body.style.overflow = '';
            },

            loadOrder(orderId) {
                this.loading = true;
                fetch('/admin/commandes/' + orderId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                })
                .then(r => r.text())
                .then(html => {
                    this.content = html;
                    this.loading = false;
                    this.$nextTick(() => this.bindForms());
                })
                .catch(() => {
                    this.content = '<p class="text-red-600 text-sm">Erreur de chargement.</p>';
                    this.loading = false;
                });
            },

            bindForms() {
                var panel = document.getElementById('panel-body');
                if (!panel) return;
                var self = this;

                panel.querySelectorAll('form[data-panel-form]').forEach(function(form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        var confirmMsg = form.querySelector('[data-confirm]')?.dataset.confirm
                                      || form.dataset.confirm;
                        if (confirmMsg && !confirm(confirmMsg)) return;

                        var formData = new FormData(form);

                        fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.deleted) {
                                self.close();
                                window.location.reload();
                                return;
                            }

                            self.panelFlash = data.success || data.error;
                            self.panelFlashType = data.success ? 'success' : 'error';

                            // Refresh panel content
                            var oid = data.order_id || self.activeOrderId;
                            if (oid) self.loadOrder(oid);
                        })
                        .catch(() => {
                            self.panelFlash = 'Une erreur est survenue.';
                            self.panelFlashType = 'error';
                        });
                    });
                });
            }
        };
    }
    </script>
@endsection
