<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $paidStatuses = ['processing', 'shipped', 'completed'];
        $yearParam = $request->get('year');
        $allTime = $yearParam === 'all';
        $year = $allTime ? null : (int) ($yearParam ?: now()->year);

        // Années disponibles
        $years = Order::selectRaw('YEAR(created_at) as y')
            ->whereIn('status', $paidStatuses)
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();

        if (! $allTime && ! in_array($year, $years) && ! empty($years)) {
            $year = $years[0];
        }

        $scopeOrder = fn ($q) => $allTime
            ? $q->whereIn('status', $paidStatuses)
            : $q->whereIn('status', $paidStatuses)->whereYear('created_at', $year);

        $scopeAll = fn ($q) => $allTime
            ? $q
            : $q->whereYear('created_at', $year);

        // Stats globales / annuelles
        $yearStats = [
            'revenue' => Order::where(fn ($q) => $scopeOrder($q))->sum('total'),
            'orders' => Order::where(fn ($q) => $scopeOrder($q))->count(),
            'items' => OrderItem::whereHas('order', fn ($q) => $scopeOrder($q))->sum('quantity'),
            'average' => Order::where(fn ($q) => $scopeOrder($q))->avg('total') ?: 0,
        ];

        // CA par mois (ou par année si global)
        if ($allTime) {
            $yearlyRevenue = Order::selectRaw('YEAR(created_at) as period, SUM(total) as revenue, COUNT(*) as orders')
                ->whereIn('status', $paidStatuses)
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            $months = [];
            foreach ($years as $y) {
                $data = $yearlyRevenue->get($y);
                $months[$y] = [
                    'name' => (string) $y,
                    'revenue' => $data->revenue ?? 0,
                    'orders' => $data->orders ?? 0,
                ];
            }
            // Inverser pour avoir l'ordre chronologique
            $months = array_reverse($months, true);
        } else {
            $monthlyRevenue = Order::selectRaw('MONTH(created_at) as month, SUM(total) as revenue, COUNT(*) as orders')
                ->whereIn('status', $paidStatuses)
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $months = [];
            $monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            for ($m = 1; $m <= 12; $m++) {
                $data = $monthlyRevenue->get($m);
                $months[$m] = [
                    'name' => $monthNames[$m],
                    'revenue' => $data->revenue ?? 0,
                    'orders' => $data->orders ?? 0,
                ];
            }
        }

        // Top produits
        $topProducts = OrderItem::select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_revenue'))
            ->whereHas('order', fn ($q) => $scopeOrder($q))
            ->groupBy('product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Stats par statut
        $statusBreakdown = Order::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->where(fn ($q) => $scopeAll($q))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return view('admin.stats.index', compact('year', 'years', 'allTime', 'yearStats', 'months', 'topProducts', 'statusBreakdown'));
    }
}
