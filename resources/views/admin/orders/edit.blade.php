@extends('adminlte::page')

@section('title', 'Edit Order')

@section('content_header')
<h1>Edit Order #{{ $order->id }}</h1>
@stop

@section('content')
<form action="{{ route('admin.orders.update', $order) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Buyer --}}
    <div class="form-group mb-3">
        <label>Buyer</label>
        <select name="user_id" class="form-control" required>
            @foreach($users as $user)
            <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
            </option>
            @endforeach
        </select>
    </div>

    {{-- Status --}}
    <div class="form-group mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            @foreach(['unpaid','paid','shipped','done'] as $st)
                <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>

    {{-- Produk --}}
    <h4 class="mt-4 mb-2">Products</h4>
    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#productModal">
        + Tambah Produk
    </button>

    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="orderTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th width="120">Original Price</th>
                    <th width="120">Discounted Price</th>
                    <th width="100">Stock</th>
                    <th width="120">Qty</th>
                    <th width="140">Subtotal</th>
                    <th width="50">#</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $product = $item->product;
                        $finalPrice = $product->final_price;
                    @endphp
                    <tr data-id="{{ $product->id }}">
                        <td>
                            <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                            <input type="hidden" name="products[{{ $product->id }}][quantity]" value="{{ $item->quantity }}" class="hidden-qty">
                            <strong>{{ $product->name }}</strong>
                            @if($product->thumbnail)
                                <div><img src="{{ asset('storage/'.$product->thumbnail->image_path) }}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>
                            @elseif($product->images->count())
                                <div><img src="{{ asset('storage/'.$product->images->first()->image_path) }}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>
                            @endif
                        </td>
                        <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td class="final-price" data-final="{{ $finalPrice }}">
                            Rp {{ number_format($finalPrice, 0, ',', '.') }}
                        </td>
                        <td>{{ $product->stock + $item->quantity }}</td>
                        <td><input type="number" min="1" max="{{ $product->stock + $item->quantity }}" value="{{ $item->quantity }}" class="form-control qty-input"></td>
                        <td class="subtotal">Rp 0</td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-product">X</button></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">Total</th>
                    <th id="grandTotal">Rp {{ number_format($order->total_price, 0, ',', '.') }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <button class="btn btn-success">Update</button>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Cancel</a>
</form>

{{-- Modal Pilih Produk --}}
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Produk</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="text" id="searchProduct" class="form-control mb-3" placeholder="Cari produk...">

                <div class="table-responsive" style="max-height:400px;overflow:auto;">
                    <table class="table table-bordered align-middle" id="productList">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Original</th>
                                <th>Discount</th>
                                <th>Stock</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $p)
                                <tr data-name="{{ strtolower($p->name) }}">
                                    <td>
                                        <strong>{{ $p->name }}</strong>
                                        @if($p->thumbnail)
                                            <div><img src="{{ asset('storage/'.$p->thumbnail->image_path) }}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>
                                        @elseif($p->images->count())
                                            <div><img src="{{ asset('storage/'.$p->images->first()->image_path) }}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                                    <td>{{ $p->final_price < $p->price ? 'Rp '.number_format($p->final_price,0,',','.') : '-' }}</td>
                                    <td>{{ $p->stock }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success add-product"
                                            data-id="{{ $p->id }}"
                                            data-name="{{ $p->name }}"
                                            data-price="{{ $p->price }}"
                                            data-final="{{ $p->final_price }}"
                                            data-stock="{{ $p->stock }}"
                                            data-image="{{ $p->thumbnail ? $p->thumbnail->image_path : ($p->images->first()->image_path ?? '') }}">
                                            Pilih
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderTableBody = document.querySelector('#orderTable tbody');
    const searchInput = document.getElementById('searchProduct');

    // 🔍 Search produk di modal
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#productList tbody tr').forEach(tr => {
            const name = tr.dataset.name;
            tr.style.display = name.includes(term) ? '' : 'none';
        });
    });

    // ➕ Tambah produk ke tabel order
    document.querySelectorAll('.add-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const finalPrice = parseFloat(this.dataset.final);
            const stock = parseInt(this.dataset.stock);
            const image = this.dataset.image;

            if (orderTableBody.querySelector(`tr[data-id="${id}"]`)) {
                alert("Produk sudah ditambahkan.");
                return;
            }

            const row = document.createElement('tr');
            row.dataset.id = id;
            row.innerHTML = `
<td>
    <input type="hidden" name="products[${id}][id]" value="${id}">
    <input type="hidden" name="products[${id}][quantity]" value="1" class="hidden-qty">
    <strong>${name}</strong>
    ${image ? `<div><img src="/storage/${image}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>` : ''}
</td>
<td>Rp ${price.toLocaleString('id-ID')}</td>
<td class="final-price" data-final="${finalPrice}">Rp ${finalPrice.toLocaleString('id-ID')}</td>
<td>${stock}</td>
<td><input type="number" min="1" max="${stock}" value="1" class="form-control qty-input"></td>
<td class="subtotal">Rp ${finalPrice.toLocaleString('id-ID')}</td>
<td><button type="button" class="btn btn-danger btn-sm remove-product">X</button></td>
`;

            orderTableBody.appendChild(row);
            recalcTotal();

            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
        });
    });

    // 🔄 Hitung ulang total
    function recalcTotal() {
        let total = 0;
        orderTableBody.querySelectorAll('tr').forEach(row => {
            const finalPrice = parseFloat(row.querySelector('.final-price').dataset.final);
            const qtyInput = row.querySelector('.qty-input');
            const qty = parseInt(qtyInput.value || 0);
            row.querySelector('.subtotal').textContent =
                new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
                    .format(finalPrice * qty);
            row.querySelector('.hidden-qty').value = qty;
            total += finalPrice * qty;
        });
        document.getElementById('grandTotal').textContent =
            new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
                .format(total);
    }

    // Jalankan pas load (biar subtotal kebaca)
    recalcTotal();

    // Delegasi event
    orderTableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input')) recalcTotal();
    });
    orderTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            e.target.closest('tr').remove();
            recalcTotal();
        }
    });
});
</script>
@stop
