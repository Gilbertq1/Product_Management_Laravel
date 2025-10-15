<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Summary
        $userCount    = User::count();
        $productCount = Product::count();
        $orderCount   = Order::count();
        $totalRevenue = Order::sum('total_price');
        $averageOrderValue = $orderCount > 0 ? round($totalRevenue / $orderCount, 2) : 0;

        // Low stock (misal < 5)
        $lowStockProducts = Product::where('stock', '<', 5)->get();

        // Orders per month
        $ordersPerMonth = [
            'labels' => [],
            'data'   => []
        ];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create()->month($i)->translatedFormat('F');
            $ordersPerMonth['labels'][] = $monthName;
            $ordersPerMonth['data'][]   = Order::whereMonth('created_at', $i)->count();
        }

        // Revenue per month
        $revenuePerMonth = [
            'labels' => [],
            'data'   => []
        ];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create()->month($i)->translatedFormat('F');
            $revenuePerMonth['labels'][] = $monthName;
            $revenuePerMonth['data'][]   = Order::whereMonth('created_at', $i)->sum('total_price');
        }

        // Users by role
        $usersByRole = User::select('role')
            ->selectRaw('count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->toArray();

        // Recent data
        $recentOrders   = Order::latest()->take(5)->get();
        $recentProducts = Product::latest()->take(5)->get();

        // Top products (produk terlaris berdasarkan quantity dari order yang selesai)
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['completed', 'paid', 'done']) // sesuaikan status valid
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // Top customers (berdasarkan total pembelian dari order yang selesai)
        $topCustomers = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereIn('orders.status', ['completed', 'paid', 'done'])
            ->select('users.id', 'users.name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_price) as total_spent'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'userCount',
            'productCount',
            'orderCount',
            'totalRevenue',
            'averageOrderValue',
            'lowStockProducts',
            'ordersPerMonth',
            'revenuePerMonth',
            'usersByRole',
            'recentOrders',
            'recentProducts',
            'topProducts',
            'topCustomers'
        ));
    }
}
