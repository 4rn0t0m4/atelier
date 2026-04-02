@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Statistiques" :breadcrumbs="['Statistiques' => null]" />

    {{-- Sélecteur d'année --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('admin.stats.index', ['year' => 'all']) }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $allTime ? 'bg-brand-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
            Global
        </a>
        @foreach($years as $y)
            <a href="{{ route('admin.stats.index', ['year' => $y]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition {{ !$allTime && $y == $year ? 'bg-brand-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $y }}
            </a>
        @endforeach
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <span class="text-xs text-gray-500 uppercase tracking-wide">CA {{ $allTime ? 'global' : $year }}</span>
            <div class="mt-2 text-2xl font-bold text-gray-800">{{ number_format($yearStats['revenue'], 2, ',', ' ') }} &euro;</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <span class="text-xs text-gray-500 uppercase tracking-wide">Commandes</span>
            <div class="mt-2 text-2xl font-bold text-gray-800">{{ number_format($yearStats['orders']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <span class="text-xs text-gray-500 uppercase tracking-wide">Articles vendus</span>
            <div class="mt-2 text-2xl font-bold text-gray-800">{{ number_format($yearStats['items']) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <span class="text-xs text-gray-500 uppercase tracking-wide">Panier moyen</span>
            <div class="mt-2 text-2xl font-bold text-gray-800">{{ number_format($yearStats['average'], 2, ',', ' ') }} &euro;</div>
        </div>
    </div>

    {{-- Graphique CA mensuel --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Chiffre d'affaires {{ $allTime ? 'par année' : 'mensuel' }}</h3>
        <div class="h-64" x-data="revenueChart()" x-init="init()">
            <canvas x-ref="chart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Tableau mensuel --}}
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Detail {{ $allTime ? 'par année' : 'par mois' }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Mois</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Commandes</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">CA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalRevenue = 0; $totalOrders = 0; @endphp
                        @foreach($months as $m => $data)
                            @if($data['revenue'] > 0 || (!$allTime && $year == now()->year && $m <= now()->month))
                                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-5 py-3 text-sm text-gray-700">{{ $data['name'] }}</td>
                                    <td class="px-5 py-3 text-sm text-right text-gray-700">{{ $data['orders'] }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-medium text-gray-800">{{ number_format($data['revenue'], 2, ',', ' ') }} &euro;</td>
                                </tr>
                                @php $totalRevenue += $data['revenue']; $totalOrders += $data['orders']; @endphp
                            @endif
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-5 py-3 text-sm text-gray-800">Total</td>
                            <td class="px-5 py-3 text-sm text-right text-gray-800">{{ $totalOrders }}</td>
                            <td class="px-5 py-3 text-sm text-right text-gray-800">{{ number_format($totalRevenue, 2, ',', ' ') }} &euro;</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top produits --}}
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Top 10 produits</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Produit</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Qté</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">CA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $item)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-5 py-3 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($item->product_name, 40) }}</td>
                                <td class="px-5 py-3 text-sm text-right text-gray-700">{{ $item->total_qty }}</td>
                                <td class="px-5 py-3 text-sm text-right font-medium text-gray-800">{{ number_format($item->total_revenue, 2, ',', ' ') }} &euro;</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500">Aucune vente pour cette annee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Répartition par statut --}}
    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Repartition par statut {{ $allTime ? '(global)' : '(' . $year . ')' }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Statut</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Commandes</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">CA</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusLabels = ['pending' => 'Non reglée', 'processing' => 'En cours', 'shipped' => 'Expédiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée'];
                    @endphp
                    @foreach($statusLabels as $key => $label)
                        @if($statusBreakdown->has($key))
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-5 py-3 text-sm"><x-admin.badge :status="$key" /></td>
                                <td class="px-5 py-3 text-sm text-right text-gray-700">{{ $statusBreakdown[$key]->count }}</td>
                                <td class="px-5 py-3 text-sm text-right font-medium text-gray-800">{{ number_format($statusBreakdown[$key]->revenue, 2, ',', ' ') }} &euro;</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function revenueChart() {
    return {
        init() {
            const ctx = this.$refs.chart.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(collect($months)->pluck('name')->values()) !!},
                    datasets: [{
                        label: 'CA (€)',
                        data: {!! json_encode(collect($months)->pluck('revenue')->values()) !!},
                        backgroundColor: 'rgba(139, 109, 71, 0.3)',
                        borderColor: 'rgb(139, 109, 71)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.parsed.y.toFixed(2).replace('.', ',') + ' €'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => v.toLocaleString('fr-FR') + ' €'
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush
