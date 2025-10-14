<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada seller
        $seller = User::firstOrCreate(
            ['email' => 'seller@test.com'],
            [
                'name' => 'Test Seller',
                'password' => bcrypt('password'),
                'role' => 'seller',
            ]
        );

        // Buat 10 produk dummy
        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'name'        => 'Produk ' . $i,
                'description' => 'Deskripsi untuk produk ' . $i,
                'price'       => rand(10000, 50000),
                'stock'       => rand(1, 100),
                'status'      => true,
                'seller_id'   => $seller->id,
                'image_url'   => null, // kosong dulu
            ]);
        }
    }
}
