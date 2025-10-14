<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $query = Order::with('user')->withCount('items');

        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('status') && $request->status !== 'trash') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $orders = $query->latest()->paginate(10)->appends($request->all());

        return view('admin.orders.index', compact('orders'));
    }

    public function create()
    {
        $users = User::all();
        // load thumbnail product juga
        $products = Product::with(['images' => function ($q) {
            $q->where('is_thumbnail', true);
        }])->latest()->paginate(20);

        return view('admin.orders.create', compact('users', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status'  => 'required|in:unpaid,paid,shipped,done',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ], [
            'products.required' => 'Minimal pilih 1 produk dengan jumlah lebih dari 0.'
        ]);

        return DB::transaction(function () use ($request) {
            $total = 0;

            $order = Order::create([
                'user_id'     => $request->user_id,
                'status'      => $request->status,
                'total_price' => 0,
            ]);

            foreach ($request->products as $index => $data) {
                $productId = $data['id'];
                $qty = (int) $data['quantity'];

                // Lock row product
                $product = Product::where('status', 1)
                    ->lockForUpdate()
                    ->findOrFail($productId);

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        "products.$index.quantity" => "Stok {$product->name} tidak cukup. Tersedia: {$product->stock}",
                    ]);
                }

                $finalPrice = $product->final_price;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'price'      => $finalPrice,
                ]);

                $product->decrement('stock', $qty);
                $product->increment('total_sold', $qty);

                $product->seller->increment('balance', $finalPrice * $qty);

                $total += $finalPrice * $qty;
            }

            $order->update(['total_price' => $total]);

            return redirect()->route('admin.orders.index')->with('success', 'Order berhasil dibuat.');
        });
    }


    public function show(Order $order)
    {
        // load thumbnail di relasi product
        $order->load(['items.product.images' => function ($q) {
            $q->where('is_thumbnail', true);
        }, 'user']);

        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $order->load(['items.product.images' => function ($q) {
            $q->where('is_thumbnail', true);
        }, 'user']);

        $users = User::where('role', 'user')->orderBy('name')->get();
        $products = Product::where('status', 1)
            ->with(['images' => function ($q) {
                $q->where('is_thumbnail', true);
            }])
            ->orderBy('name')
            ->get();

        $quantities = $order->items->pluck('quantity', 'product_id')->toArray();

        return view('admin.orders.edit', compact('order', 'users', 'products', 'quantities'));
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'user_id'  => ['required', 'exists:users,id'],
            'status'   => ['required', 'in:unpaid,paid,shipped,done'],
            'products' => ['required', 'array'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($request, $order) {
            // rollback stok lama
            foreach ($order->items as $oldItem) {
                if ($p = Product::find($oldItem->product_id)) {
                    $p->increment('stock', $oldItem->quantity);
                    $p->decrement('total_sold', $oldItem->quantity);
                }
            }

            $order->items()->delete();

            $total = 0;
            $addedItems = 0;

            foreach ($request->products as $productId => $data) {
                $qty = (int)($data['quantity'] ?? 0);
                if ($qty <= 0) continue;

                // Lock row product
                $product = Product::where('status', 1)
                    ->lockForUpdate()
                    ->findOrFail($productId);

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        "products.$productId.quantity" => "Stok {$product->name} tidak cukup. Tersedia: {$product->stock}",
                    ]);
                }

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $qty,
                    'price'      => $product->final_price,
                ]);

                $product->decrement('stock', $qty);
                $product->increment('total_sold', $qty);

                $total += $product->final_price * $qty;
                $addedItems++;
            }

            if ($addedItems === 0) {
                throw ValidationException::withMessages([
                    'products' => 'Minimal pilih 1 produk dengan quantity > 0.',
                ]);
            }

            $order->update([
                'user_id'     => $request->user_id,
                'status'      => $request->status,
                'total_price' => $total,
            ]);

            $this->logActivity('update', "Update order #{$order->id} → Status: {$order->status}");

            return redirect()->route('admin.orders.index')->with('success', 'Order updated!');
        });
    }

    public function destroy(Order $order)
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($p = Product::find($item->product_id)) {
                    $p->increment('stock', $item->quantity);
                    $p->decrement('total_sold', $item->quantity);
                }
            }
            $order->delete();

            $this->logActivity('delete', "Soft delete order #{$order->id}");

            return redirect()->route('admin.orders.index')->with('success', 'Order moved to trash.');
        });
    }

    public function restore($id)
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->restore();

        $this->logActivity('restore', "Restore order #{$order->id}");

        return redirect()->route('admin.orders.index')->with('success', 'Order restored successfully.');
    }

    public function forceDelete($id)
    {
        return DB::transaction(function () use ($id) {
            $order = Order::onlyTrashed()->with('items')->findOrFail($id);

            foreach ($order->items as $item) {
                if ($p = Product::find($item->product_id)) {
                    $p->decrement('total_sold', $item->quantity);
                }
            }

            $order->items()->forceDelete();
            $order->forceDelete();

            $this->logActivity('force_delete', "Force delete order #{$order->id}");

            return redirect()->route('admin.orders.index')->with('success', 'Order permanently deleted.');
        });
    }

    public function pay(Order $order)
    {
        if ($order->status !== 'unpaid') {
            return back()->with('error', 'Order sudah dibayar atau tidak valid.');
        }

        $buyer = $order->user;
        $total = $order->total_price;

        // Cek saldo buyer cukup
        if ($buyer->balance < $total) {
            return back()->with('error', 'Saldo buyer tidak mencukupi untuk membayar order ini.');
        }

        DB::transaction(function () use ($order, $buyer, $total) {
            // 1. Kurangi saldo buyer
            $buyer->decrement('balance', $total);

            // 2. Tambahkan saldo seller berdasarkan order items
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && $product->user) {
                    $seller = $product->user;
                    $seller->increment('balance', $item->final_price * $item->quantity);
                }
            }

            // 3. Ubah status order jadi paid
            $order->update(['status' => 'paid']);
        });

        return back()->with('success', 'Order berhasil dibayar. Saldo buyer terpotong & seller menerima pembayaran.');
    }


    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:orders,id',
            'action' => 'required|string|in:delete,restore,force_delete,mark_paid,mark_shipped,mark_done',
        ]);

        $ids = $request->ids;
        $message = '';

        switch ($request->action) {
            case 'delete':
                foreach (Order::whereIn('id', $ids)->with('items')->get() as $order) {
                    foreach ($order->items as $item) {
                        if ($p = Product::find($item->product_id)) {
                            $p->increment('stock', $item->quantity);
                            $p->decrement('total_sold', $item->quantity);
                        }
                    }
                    $order->delete();
                }
                $message = "Selected orders moved to trash.";
                $this->logActivity('delete', "Soft delete orders ID: " . implode(', ', $ids));
                break;

            case 'restore':
                Order::onlyTrashed()->whereIn('id', $ids)->restore();
                $message = "Selected orders restored.";
                $this->logActivity('restore', "Restore orders ID: " . implode(', ', $ids));
                break;

            case 'force_delete':
                foreach (Order::onlyTrashed()->whereIn('id', $ids)->with('items')->get() as $order) {
                    foreach ($order->items as $item) {
                        if ($p = Product::find($item->product_id)) {
                            $p->decrement('total_sold', $item->quantity);
                        }
                    }
                    $order->items()->forceDelete();
                    $order->forceDelete();
                }
                $message = "Selected orders permanently deleted.";
                $this->logActivity('force_delete', "Force delete orders ID: " . implode(', ', $ids));
                break;

            case 'mark_paid':
                Order::whereIn('id', $ids)->update(['status' => 'paid']);
                $message = "Selected orders marked as PAID.";
                $this->logActivity('update', "Order ditandai PAID ID: " . implode(', ', $ids));
                break;

            case 'mark_shipped':
                Order::whereIn('id', $ids)->update(['status' => 'shipped']);
                $message = "Selected orders marked as SHIPPED.";
                $this->logActivity('update', "Order ditandai SHIPPED ID: " . implode(', ', $ids));
                break;

            case 'mark_done':
                Order::whereIn('id', $ids)->update(['status' => 'done']);
                $message = "Selected orders marked as DONE.";
                $this->logActivity('update', "Order ditandai DONE ID: " . implode(', ', $ids));
                break;
        }

        return redirect()->route('admin.orders.index')->with('success', $message);
    }
}
