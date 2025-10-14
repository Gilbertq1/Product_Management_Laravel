<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    public function created(OrderItem $item): void
    {
        $item->order?->recalcTotal();
    }

    public function updated(OrderItem $item): void
    {
        $item->order?->recalcTotal();
    }

    public function deleted(OrderItem $item): void
    {
        $item->order?->recalcTotal();
    }
}
