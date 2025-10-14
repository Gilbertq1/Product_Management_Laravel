<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "deleting" event.
     */
    public function deleting(Product $product): void
    {
        // Loop semua order item yang terkait produk ini
        foreach ($product->orderItems as $item) {
            $order = $item->order;

            // Hapus item dari order
            $item->delete();

            // Update total order
            if ($order) {
                $order->recalcTotal();
            }
        }
    }
}
