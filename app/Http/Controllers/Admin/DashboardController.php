<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $paidStatuses = ['processing', 'shipped', 'completed'];

        $metrics = [
            'orders_count' => Order::whereIn('status', $paidStatuses)->count(),
            'revenue' => Order::whereIn('status', $paidStatuses)->sum('total'),
            'products_count' => Product::where('is_active', true)->count(),
            'customers_count' => User::where('is_admin', false)->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(10)->get();

        return view('admin.dashboard.index', compact('metrics', 'recentOrders'));
    }
}
