<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'seller_id',
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'status',
        'total_sold',
    ];

    protected $casts = [
        'status' => 'boolean',
        'price' => 'decimal:2',
        'total_sold' => 'integer',
    ];

    // Relasi ke Seller (User)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Relasi ke Order Items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function thumbnail()
    {
        return $this->hasOne(ProductImage::class)->where('is_thumbnail', true);
    }

    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return 'storage/' . $this->thumbnail->image_path;
        }
        return null;
    }


    // Relasi ke Categories
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    // Relasi ke Discounts
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    // Scope buat ambil diskon aktif
    public function activeDiscount()
    {
        return $this->discounts()
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('created_at');
    }

    // Harga setelah diskon
    public function getFinalPriceAttribute()
    {
        $discount = $this->activeDiscount()->first();

        if ($discount) {
            if ($discount->type === 'percent') {
                return max(0, $this->price - ($this->price * $discount->value / 100));
            }
            if ($discount->type === 'fixed') {
                return max(0, $this->price - $discount->value);
            }
        }

        return $this->price;
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function isAvailable(): bool
    {
        return $this->status && $this->stock > 0;
    }
}
