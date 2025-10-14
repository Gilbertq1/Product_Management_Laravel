<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
        $table->string('name', 150);
        $table->text('description')->nullable();
        $table->decimal('price', 12, 2);
        $table->integer('stock')->default(0);
        $table->string('image_url', 255)->nullable();
        $table->boolean('status')->default(true);
        $table->timestamps();

        $table->index('seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
