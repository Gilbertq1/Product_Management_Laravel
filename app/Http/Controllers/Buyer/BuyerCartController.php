<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\LogActivity;
use Illuminate\Support\Facades\DB;
use App\Jobs\ExpireOrderJob;

class BuyerCartController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        $selected = $request->old('selected', []);

        return view('buyer.cart.index', compact('cart', 'selected'));
    }

    public function addToCart($id)
    {
        $product = Product::findOrFail($id);

        // cek status aktif
        if (! $product->status) {
            $this->logActivity('cart.add_failed', "Gagal menambahkan product ID {$id} - produk inactive");
            return redirect()->route('buyer.products.index')
                ->with('error', 'Produk tidak tersedia untuk dibeli.');
        }

        // cek availability (status + stock)
        if (! $product->isAvailable()) {
            $this->logActivity('cart.add_failed', "Gagal menambahkan product ID {$id} - stok habis atau tidak tersedia");
            return redirect()->route('buyer.products.index')
                ->with('error', 'Produk sudah habis, tidak bisa dipesan.');
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] < $product->stock) {
                $cart[$id]['quantity']++;
            } else {
                $this->logActivity('cart.add_failed', "Gagal menambahkan product ID {$id} - stok tidak mencukupi untuk tambah qty");
                return redirect()->route('buyer.cart.index')
                    ->with('error', 'Stok produk tidak mencukupi.');
            }
        } else {
            $thumbnail = $product->images()->where('is_thumbnail', true)->first();
            $cart[$id] = [
                'name'           => $product->name,
                'price'          => $product->final_price,
                'original_price' => $product->price,
                'stock'          => $product->stock,
                'quantity'       => 1,
                'thumbnail'      => $thumbnail ? $thumbnail->image_path : null,
            ];
        }

        session()->put('cart', $cart);

        $this->logActivity('cart.add', "Menambahkan product ID {$id} ({$cart[$id]['name']}) ke keranjang, qty={$cart[$id]['quantity']}");

        return redirect()->route('buyer.cart.index')->with('success', 'Produk ditambahkan ke keranjang');
    }

    public function updateCart(Request $request, $id)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$id])) {
            $oldQty = $cart[$id]['quantity'];

            // pastikan tidak melebihi stock terakhir di DB
            $product = Product::find($id);
            $maxStock = $product ? $product->stock : $cart[$id]['stock'];

            $newQty = min(intval($request->quantity), $maxStock);
            $newQty = $newQty < 1 ? 1 : $newQty;

            $cart[$id]['quantity'] = $newQty;
            session()->put('cart', $cart);

            $this->logActivity('cart.update', "Update qty product ID {$id} ({$cart[$id]['name']}) dari {$oldQty} ke {$newQty}");
        }

        return redirect()->route('buyer.cart.index')->withInput($request->only('selected'));
    }

    public function removeFromCart($id)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$id])) {
            $removedName = $cart[$id]['name'] ?? "ID {$id}";
            $removedQty = $cart[$id]['quantity'] ?? null;

            unset($cart[$id]);
            session()->put('cart', $cart);

            $this->logActivity('cart.remove', "Menghapus product {$removedName} (ID: {$id}) dari keranjang, qty={$removedQty}");
        }
        return redirect()->route('buyer.cart.index');
    }

    public function checkout(Request $request)
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('buyer.cart.index')->with('error', 'Keranjang belanja kosong.');
        }

        $selected = (array) $request->input('selected', []);

        if (empty($selected)) {
            return redirect()->route('buyer.cart.index')
                ->with('error', 'Pilih minimal 1 produk untuk checkout.')
                ->withInput(['selected' => $selected]);
        }

        // Ambil hanya item yang ada di cart dan termasuk selected
        $filteredCart = [];
        foreach ($selected as $productId) {
            if (!isset($cart[$productId])) {
                continue;
            }

            $item = $cart[$productId];
            $product = Product::find($productId);

            if (!$product) {
                continue;
            }

            // cek ketersediaan produk (isAvailable mempertimbangkan status & stock)
            if (!$product->isAvailable() || $product->stock < $item['quantity']) {
                continue;
            }

            $filteredCart[$productId] = [
                'product'  => $product,
                'quantity' => $item['quantity'],
            ];
        }

        if (empty($filteredCart)) {
            return redirect()->route('buyer.cart.index')
                ->with('error', 'Tidak ada produk terpilih yang tersedia untuk dipesan.')
                ->withInput(['selected' => $selected]);
        }

        try {
            $order = DB::transaction(function () use ($filteredCart, &$cart) {
                $total_price = collect($filteredCart)->sum(fn($item) => $item['product']->final_price * $item['quantity']);

                $order = Order::create([
                    'user_id'     => Auth::id(),
                    'status'      => 'unpaid',
                    'total_price' => $total_price,
                ]);

                foreach ($filteredCart as $productId => $item) {
                    // lock row untuk mencegah race condition stok
                    $product = Product::where('id', $productId)->lockForUpdate()->first();
                    $quantity = $item['quantity'];
                    $finalPrice = $product->final_price;

                    if ($product->stock < $quantity || !$product->status) {
                        throw new \Exception("Stok {$product->name} tidak mencukupi atau produk tidak tersedia.");
                    }

                    $product->decrement('stock', $quantity);

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $productId,
                        'quantity'   => $quantity,
                        'price'      => $finalPrice,
                    ]);

                    // Hapus dari session cart hanya item yang dipesan
                    if (isset($cart[$productId])) {
                        unset($cart[$productId]);
                    }
                }

                // simpan kembali cart yang hanya berisi item tersisa (jika ada)
                session()->put('cart', $cart);

                return $order;
            });

            // dispatch expire job
            ExpireOrderJob::dispatch($order->id)->delay(now()->addMinutes(30));

            // log: sukses membuat order (cantumkan id & total)
            $this->logActivity('order.created', "Order ID {$order->id} dibuat oleh user_id " . Auth::id() . ", total_price={$order->total_price}");

            return redirect()->route('buyer.orders.index')->with('success', 'Pesanan berhasil dibuat, segera lakukan pembayaran.');
        } catch (\Exception $e) {
            // log: kegagalan checkout dengan pesan error
            $this->logActivity('checkout.failed', "Gagal checkout: " . $e->getMessage());

            // kembalikan selected agar checkbox tetap tercentang pada tampilan
            return redirect()->route('buyer.cart.index')
                ->with('error', $e->getMessage())
                ->withInput(['selected' => $selected]);
        }
    }

    public function cancel(Order $order)
    {
        if ($order->user_id !== Auth::id() || $order->status !== 'unpaid') {
            $this->logActivity('order.cancel_failed', "Percobaan membatalkan order ID {$order->id} gagal - tidak valid atau status bukan 'unpaid'");
            return back()->with('error', 'Pesanan tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            $order->update(['status' => 'cancelled']);
        });

        $this->logActivity('order.cancelled', "Order ID {$order->id} dibatalkan oleh user_id " . Auth::id());

        return back()->with('success', 'Pesanan berhasil dibatalkan.');
    }
}