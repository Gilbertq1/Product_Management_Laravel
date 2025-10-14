<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Hapus foreign key lama
            $table->dropForeign(['product_id']);

            // Bikin foreign key baru pakai restrictOnDelete
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Rollback ke cascadeOnDelete
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->cascadeOnDelete();
        });
    }
};
