<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;

class AdminAnalyticsController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'paid')->sum('total_price');
        $totalProducts = Product::count();

        $salesPerMonth = Order::selectRaw('MONTH(created_at) as month, SUM(total_price) as total')
            ->where('status', 'paid')
            ->groupBy('month')
            ->pluck('total', 'month');

        $topProducts = Product::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        return view('admin.analytics.index', compact(
            'totalUsers', 'totalOrders', 'totalRevenue', 'totalProducts',
            'salesPerMonth', 'topProducts'
        ));
    }
}
