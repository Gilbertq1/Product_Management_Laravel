<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];
    
    protected $fillable = [
        'product_id',
        'type',
        'value',
        'start_date',
        'end_date'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // cek apakah diskon aktif
    public function isActive()
    {
        $now = now();
        return (!$this->start_date || $now >= $this->start_date) &&
            (!$this->end_date || $now <= $this->end_date);
    }
}
