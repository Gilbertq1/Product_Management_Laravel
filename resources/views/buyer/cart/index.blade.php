@extends('layouts.buyer')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="container my-5">
    <h2 class="fw-bold mb-4 text-center">🛒 Keranjang Belanja</h2>

    {{-- ALERT --}}
    @if(session('error'))
    <div class="alert alert-danger text-center">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success text-center">{{ session('success') }}</div>
    @endif

    @if(!empty($cart) && count($cart) > 0)
    <div class="row g-4">
        {{-- PRODUK LIST --}}
        <div class="col-lg-8">
            @php $grandTotal = 0; @endphp
            @foreach($cart as $id => $item)
            @php
            $total = $item['price'] * $item['quantity'];
            $grandTotal += $total;
            // determine if checkbox should be checked (old input has priority)
            $oldSelected = old('selected', $selected ?? []);
            $isChecked = in_array($id, (array) $oldSelected);
            @endphp

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="form-check me-3">
                        <input type="checkbox" form="checkoutForm" name="selected[]" value="{{ $id }}"
                            class="form-check-input select-product"
                            data-price="{{ $item['price'] }}" data-qty="{{ $item['quantity'] }}"
                            {{ $isChecked ? 'checked' : '' }}>
                    </div>

                    <div class="flex-grow-1 d-flex align-items-center gap-3">
                        <img src="{{ isset($item['thumbnail']) 
                ? asset('storage/'.$item['thumbnail']) 
                : 'https://via.placeholder.com/80' }}"
                            class="rounded" width="80" height="80" alt="{{ $item['name'] }}">

                        <div>
                            <h6 class="mb-1">{{ $item['name'] }}</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-success">
                                    Rp{{ number_format($item['price'], 0, ',', '.') }}
                                </span>
                                @if(isset($item['original_price']) && $item['original_price'] > $item['price'])
                                <span class="text-muted text-decoration-line-through small">
                                    Rp{{ number_format($item['original_price'], 0, ',', '.') }}
                                </span>
                                @endif
                            </div>
                            <small class="text-muted">Stok: {{ $item['stock'] ?? 'N/A' }}</small>
                        </div>
                    </div>

                    <div class="text-end">
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" value="{{ $item['quantity'] }}"
                                class="form-control quantity-input"
                                style="width: 70px;"
                                data-id="{{ $id }}" data-price="{{ $item['price'] }}"
                                min="1" max="{{ $item['stock'] }}">
                            <button type="button" class="btn btn-sm btn-outline-primary update-btn"
                                data-id="{{ $id }}">Update</button>
                        </div>
                        <div class="mt-2 fw-bold text-primary item-total" id="total-{{ $id }}">
                            Rp{{ number_format($total, 0, ',', '.') }}
                        </div>

                        {{-- hapus (SweetAlert akan menangani konfirmasi) --}}
                        <form action="{{ route('buyer.cart.remove', $id) }}" method="POST"
                            class="mt-2 delete-form" data-name="{{ $item['name'] }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- RINGKASAN --}}
        <div class="col-lg-4">
            <div class="card shadow-lg border-0 sticky-top" style="top:100px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Ringkasan Belanja</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Produk Dipilih:</span>
                        <span id="selectedTotal" class="fw-bold text-success">Rp0</span>
                    </div>
                    <hr>
                    <form action="{{ route('buyer.cart.checkout') }}" method="POST" id="checkoutForm">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            Checkout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info text-center mt-5 p-4 rounded shadow-sm">
        Keranjang belanja masih kosong 🥀 <br>
        <a href="{{ route('buyer.products.index') }}" class="btn btn-primary mt-3">
            Belanja Sekarang
        </a>
    </div>
    @endif
</div>

{{-- SWEETALERT2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- SCRIPT --}}
<script>
    // helper: format number ke Rupiah (menggunakan toLocaleString)
    function formatRupiah(num) {
        return 'Rp' + Number(num || 0).toLocaleString('id-ID');
    }

    // ambil elemen
    const totalElement = document.getElementById('selectedTotal');

    // ambil semua checkbox dan quantity inputs
    const checkboxes = document.querySelectorAll('.select-product');
    const qtyInputs = document.querySelectorAll('.quantity-input');

    // update total berdasarkan checkbox yang dicentang dan quantity saat ini
    function updateTotal() {
        let sum = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                const id = cb.value;
                // cari input quantity yang sesuai id
                const qtyInput = document.querySelector(`.quantity-input[data-id="${id}"]`);
                const price = parseFloat(cb.dataset.price) || 0;
                const qty = qtyInput ? parseInt(qtyInput.value) || 0 : parseInt(cb.dataset.qty) || 0;
                sum += price * qty;
            }
        });
        totalElement.textContent = formatRupiah(sum);
    }

    // inisialisasi: panggil di load agar ringkasan muncul sesuai checkbox awal
    document.addEventListener('DOMContentLoaded', function() {
        updateTotal();
    });

    // kalau user ubah quantity langsung (input), update total secara realtime
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', function() {
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max) || Infinity;
            if (this.value === '' || isNaN(this.value)) {
                this.value = min;
            }
            if (parseInt(this.value) < min) this.value = min;
            if (parseInt(this.value) > max) this.value = max;
            updateTotal();
        });
    });

    // Update button: kirim request ke backend untuk update quantity dan update UI
    document.querySelectorAll('.update-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const input = document.querySelector(`.quantity-input[data-id="${id}"]`);
            const qty = parseInt(input.value) || 1;
            const price = parseFloat(input.dataset.price) || 0;

            // update UI subtotal lokal
            document.getElementById(`total-${id}`).textContent = formatRupiah(qty * price);

            // update dataset qty pada checkbox (agar konsisten jika belum reload)
            const checkbox = document.querySelector(`.select-product[value="${id}"]`);
            if (checkbox) checkbox.dataset.qty = qty;

            updateTotal();

            // kirim update ke backend (method PUT)
            try {
                await fetch(`/buyer/cart/update/${id}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: qty })
                });
                // opsional: tampilkan notifikasi kecil (Toast)
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1400,
                    icon: 'success',
                    title: 'Jumlah diperbarui'
                });
            } catch (err) {
                console.error(err);
                Swal.fire('Gagal', 'Gagal memperbarui jumlah produk.', 'error');
            }
        });
    });

    // checkbox change -> update total
    checkboxes.forEach(cb => cb.addEventListener('change', updateTotal));

    // Konfirmasi hapus dengan SweetAlert2
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const itemName = this.dataset.name || 'produk ini';
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: `Hapus "${itemName}" dari keranjang?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });

    // Validasi sebelum submit checkout: wajib ada minimal 1 checkbox tercentang
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.select-product:checked');
        if (checked.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Pilih produk',
                text: 'Silakan centang minimal 1 produk yang ingin dibeli.',
                confirmButtonText: 'Oke'
            });
            return false;
        }

        // Pastikan quantity minimal 1 untuk setiap produk yang dipilih
        for (const cb of checked) {
            const id = cb.value;
            const qtyInput = document.querySelector(`.quantity-input[data-id="${id}"]`);
            if (!qtyInput || parseInt(qtyInput.value) < 1) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Jumlah tidak valid',
                    text: 'Pastikan jumlah setiap produk minimal 1.',
                    confirmButtonText: 'Oke'
                });
                return false;
            }
        }
    });

    // Jika ada flash success message, tunjukkan toast SweetAlert (opsional)
    @if(session('success'))
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1800,
        icon: 'success',
        title: "{{ session('success') }}"
    });
    @endif
</script>
@endsection
