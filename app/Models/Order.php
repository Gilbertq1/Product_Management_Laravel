<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function recalcTotal(): void
    {
        $this->total_price = $this->calculateTotal();
        $this->save();
    }

    // Relasi ke User (Customer)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Order Items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Hitung ulang total harga dari items
    public function calculateTotal(): float
    {
        return $this->items->sum(fn($item) => $item->subtotal);
    }
}
