<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // tambah saldo
    public function credit($amount, $desc = null, $ref = null)
    {
        $this->increment('balance', $amount);
        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'description' => $desc,
            'reference_id' => $ref,
        ]);
    }

    // kurangi saldo
    public function debit($amount, $desc = null, $ref = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception("Saldo tidak cukup");
        }

        $this->decrement('balance', $amount);
        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'description' => $desc,
            'reference_id' => $ref,
        ]);
    }
}
