<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    // Tampilkan semua item dalam satu order
    public function index(Order $order)
    {
        $items = $order->items()->with('product')->get();
        return view('admin.order_items.index', compact('order', 'items'));
    }

    // Form untuk tambah item baru ke order
    public function create(Order $order)
    {
        $products = Product::where('status', true)->get(); // hanya product aktif
        return view('admin.order_items.create', compact('order', 'products'));
    }

    // Simpan item baru ke order
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->status) {
            return redirect()->back()->withErrors('Product inactive, cannot add to order.');
        }

        // Tambah item
        $item = new OrderItem();
        $item->order_id   = $order->id;
        $item->product_id = $product->id;
        $item->quantity   = $request->quantity;
        $item->price      = $product->price;
        $item->save();

        return redirect()->route('admin.order_items.index', $order->id)
            ->with('success', 'Item added to order successfully!');
    }

    // Form edit item (misal ubah qty)
    public function edit(Order $order, OrderItem $orderItem)
    {
        $products = Product::where('status', true)->get();
        return view('admin.order_items.edit', compact('order', 'orderItem', 'products'));
    }

    // Update item (qty / product)
    public function update(Request $request, Order $order, OrderItem $orderItem)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        if (!$product->status) {
            return redirect()->back()->withErrors('Product inactive, cannot assign to order.');
        }

        $orderItem->product_id = $product->id;
        $orderItem->quantity   = $request->quantity;
        $orderItem->price      = $product->price;
        $orderItem->save();

        return redirect()->route('admin.order_items.index', $order->id)
            ->with('success', 'Order item updated successfully!');
    }

    // Hapus item dari order
    public function destroy(Order $order, OrderItem $orderItem)
    {
        $orderItem->delete();

        // update total order
        $order->recalcTotal();

        return redirect()->route('admin.order_items.index', $order->id)
            ->with('success', 'Order item deleted successfully!');
    }
}
