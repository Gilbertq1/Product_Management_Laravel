<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Product $product)
    {
        $discounts = $product->discounts()->latest()->get();
        return view('admin.discounts.index', compact('product', 'discounts'));
    }

    public function create(Product $product)
    {
        return view('admin.discounts.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $product->discounts()->create($request->all());

        return redirect()->route('admin.products.discounts.index', $product)
            ->with('success', 'Discount created successfully.');
    }

    public function edit(Product $product, Discount $discount)
    {
        return view('admin.discounts.edit', compact('product', 'discount'));
    }

    public function update(Request $request, Product $product, Discount $discount)
    {
        $request->validate([
            'type'       => 'required|in:percent,fixed',
            'value'      => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $discount->update($request->all());

        return redirect()->route('admin.products.discounts.index', $product)
            ->with('success', 'Discount updated successfully.');
    }

    public function destroy(Product $product, Discount $discount)
    {
        $discount->delete();

        return redirect()->route('admin.products.discounts.index', $product)
            ->with('success', 'Discount deleted successfully.');
    }

    public function indexAll()
    {
        $discounts = \App\Models\Discount::with('product')->latest()->get();
        return view('admin.discounts.all', compact('discounts'));
    }
}
