@extends('adminlte::page')

@section('title', 'Create Order')

@section('content_header')
<h1>Create Order</h1>
@stop

@section('content')
<form action="{{ route('admin.orders.store') }}" method="POST">
    @csrf

    {{-- Buyer --}}
    <div class="form-group mb-3">
        <label>Buyer</label>
        <select name="user_id" class="form-control" required>
            @foreach($users as $user)
            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
        </select>
    </div>

    {{-- Status --}}
    <div class="form-group mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="unpaid" selected>Unpaid</option>
            <option value="paid">Paid</option>
            <option value="shipped">Shipped</option>
            <option value="done">Done</option>
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
                {{-- Produk akan ditambahkan via JS --}}
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">Total</th>
                    <th id="grandTotal">Rp 0</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <button class="btn btn-success">Save</button>
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
                                @php
                                    $img = $p->thumbnail ? $p->thumbnail->image_path : ($p->images->first()->image_path ?? null);
                                    $isActive = (bool) $p->status;
                                    $isInStock = $p->stock > 0;
                                @endphp
                                <tr data-name="{{ strtolower($p->name) }}" data-status="{{ $isActive ? 'active' : 'inactive' }}">
                                    <td style="vertical-align:middle;">
                                        <div class="d-flex align-items-center">
                                            @if($img)
                                                <img src="{{ asset('storage/'.$img) }}" width="60" height="60" style="object-fit:cover;border-radius:6px;margin-right:10px;">
                                            @else
                                                <div style="width:60px;height:60px;background:#f0f0f0;border-radius:6px;display:inline-block;margin-right:10px;"></div>
                                            @endif
                                            <div>
                                                <strong>{{ $p->name }}</strong>
                                                <div>
                                                    @if(!$isActive)
                                                        <span class="badge badge-secondary">Inactive</span>
                                                    @elseif(!$isInStock)
                                                        <span class="badge badge-warning">Out of stock</span>
                                                    @else
                                                        <span class="badge badge-success">Active</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="vertical-align:middle;">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                                    <td style="vertical-align:middle;">
                                        @if($p->final_price < $p->price)
                                            Rp {{ number_format($p->final_price, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td style="vertical-align:middle;">{{ $p->stock }}</td>
                                    <td style="vertical-align:middle;">
                                        @if(!$isActive)
                                            <button type="button" class="btn btn-sm btn-secondary" disabled>Inactive</button>
                                        @elseif(!$isInStock)
                                            <button type="button" class="btn btn-sm btn-secondary" disabled>Out of stock</button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-success add-product"
                                                data-id="{{ $p->id }}"
                                                data-name="{{ e($p->name) }}"
                                                data-price="{{ $p->price }}"
                                                data-final="{{ $p->final_price }}"
                                                data-stock="{{ $p->stock }}"
                                                data-image="{{ $img ? $img : '' }}">
                                                Pilih
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if($products->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada produk.</td>
                                </tr>
                            @endif
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
    const productList = document.getElementById('productList');

    // 🔍 Search produk di modal (filter by name)
    searchInput.addEventListener('input', function() {
        const term = this.value.trim().toLowerCase();
        document.querySelectorAll('#productList tbody tr').forEach(tr => {
            const name = tr.dataset.name || '';
            tr.style.display = name.includes(term) ? '' : 'none';
        });
    });

    // ➕ Delegated: Tambah produk ke tabel order (hanya untuk tombol aktif)
    productList.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-product');
        if (!btn) return;
        if (btn.disabled) return;

        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price) || 0;
        const finalPrice = parseFloat(btn.dataset.final) || 0;
        const stock = parseInt(btn.dataset.stock) || 0;
        const image = btn.dataset.image || '';

        // jika sudah ada di tabel
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
    ${image ? `<div style="margin-top:6px;"><img src="/storage/${image}" width="60" height="60" style="object-fit:cover;border-radius:6px;"></div>` : ''}
</td>
<td>Rp ${price.toLocaleString('id-ID')}</td>
<td class="final-price" data-final="${finalPrice}">Rp ${finalPrice.toLocaleString('id-ID')}</td>
<td>${stock}</td>
<td><input type="number" min="1" max="${stock}" value="1" class="form-control qty-input" style="width:100px;"></td>
<td class="subtotal">Rp ${finalPrice.toLocaleString('id-ID')}</td>
<td><button type="button" class="btn btn-danger btn-sm remove-product">X</button></td>
`;
        orderTableBody.appendChild(row);
        recalcTotal();

        // tutup modal (Bootstrap)
        const modalEl = document.getElementById('productModal');
        const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        bsModal.hide();
    });

    // 🔄 Hitung ulang total dan update hidden qty
    function recalcTotal() {
        let total = 0;
        orderTableBody.querySelectorAll('tr').forEach(row => {
            const finalPrice = parseFloat(row.querySelector('.final-price').dataset.final) || 0;
            const qtyInput = row.querySelector('.qty-input');
            const qty = Math.max(1, parseInt(qtyInput.value || 0));
            const subtotal = finalPrice * qty;

            row.querySelector('.subtotal').textContent =
                new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
                    .format(subtotal);

            const hiddenQty = row.querySelector('.hidden-qty');
            if (hiddenQty) hiddenQty.value = qty;

            total += subtotal;
        });

        document.getElementById('grandTotal').textContent =
            new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 })
                .format(total);
    }

    // Delegasi event: update qty & remove
    orderTableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input')) {
            const input = e.target;
            const max = parseInt(input.max || 0);
            let val = parseInt(input.value || 1);
            if (isNaN(val) || val < 1) val = 1;
            if (max && val > max) val = max;
            input.value = val;
            recalcTotal();
        }
    });

    orderTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            e.target.closest('tr').remove();
            recalcTotal();
        }
    });

    // Optional: ketika form submit, pastikan ada products
    document.querySelector('form').addEventListener('submit', function(e) {
        if (orderTableBody.querySelectorAll('tr').length === 0) {
            e.preventDefault();
            alert('Minimal pilih 1 produk untuk membuat order.');
            return false;
        }
    });
});
</script>
@stop