<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\LogActivity;

class CheckoutController extends Controller
{
    use LogActivity;

    public function pay(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            return back()->with('error', 'Order tidak ditemukan.');
        }

        if ($order->status !== 'unpaid') {
            return back()->with('info', 'Order ini sudah diproses.');
        }

        if ($user->balance < $order->total_price) {
            return back()->with('error', 'Saldo tidak cukup.');
        }

        try {
            DB::transaction(function () use ($user, $order) {
                // 1. Kurangi saldo buyer
                $user->decrement('balance', $order->total_price);

                // 2. Tambahkan saldo seller berdasarkan order items
                foreach ($order->items as $item) {
                    if ($item->product && $item->product->seller) {
                        $item->product->seller->increment('balance', $item->price * $item->quantity);
                    }
                }

                // 3. Update status order jadi paid
                $order->update(['status' => 'paid']);

                // 4. Log activity
                $this->logActivity(
                    'checkout',
                    "User {$user->name} membayar Order #{$order->id} senilai Rp{$order->total_price}"
                );
            });

            return redirect()
                ->route('buyer.orders.show', $order->id)
                ->with('success', 'Pembayaran berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal bayar: ' . $e->getMessage());
        }
    }
}
