<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

class DashboardController extends Controller
{
    public function index()
    {
        $userCount = User::count();
        $productCount = Product::count();
        $orderCount = Order::count();

        $recentOrders = Order::latest()->take(5)->get();
        $recentProducts = Product::latest()->take(5)->get();

        // Orders per bulan
        $ordersPerMonth = [
            'labels' => Order::selectRaw('MONTH(created_at) as month')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('month')
                ->map(fn($m) => \Carbon\Carbon::create()->month($m)->format('M'))
                ->toArray(),
            'data' => Order::selectRaw('COUNT(*) as count, MONTH(created_at) as month')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count')
                ->toArray(),
        ];

        // Users by role
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        $lowStockProducts = Product::where('stock', '<', 5)->get();

        return view('admin.dashboard', compact(
            'userCount',
            'productCount',
            'orderCount',
            'recentOrders',
            'recentProducts',
            'ordersPerMonth',
            'usersByRole',
            'lowStockProducts'
        ));
    }
}
