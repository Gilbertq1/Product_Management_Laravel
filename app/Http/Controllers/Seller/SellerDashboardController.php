<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\OrderItem;

class SellerDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Jumlah produk milik seller
        $totalProducts = Product::where('seller_id', $user->id)->count();

        // Total item terjual
        $totalSold = OrderItem::whereHas('product', function ($q) use ($user) {
            $q->where('seller_id', $user->id);
        })->sum('quantity');

        // Produk terlaris
        $bestSeller = OrderItem::selectRaw('product_id, SUM(quantity) as total')
            ->whereHas('product', fn($q) => $q->where('seller_id', $user->id))
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->with('product')
            ->first();

        return view('seller.dashboard', compact(
            'user',
            'totalProducts',
            'totalSold',
            'bestSeller'
        ));
    }
}