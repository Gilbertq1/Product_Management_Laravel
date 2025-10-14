@extends('layouts.buyer')

@section('title', 'Order Detail')

@section('content')
<div class="mb-4">
    <h2>Order #{{ $order->id }}</h2>
    <p><strong>Status:</strong> 
        <span class="badge 
            @if($order->status === 'paid') bg-success 
            @elseif($order->status === 'unpaid') bg-warning text-dark
            @elseif($order->status === 'cancelled') bg-danger
            @elseif($order->status === 'expired') bg-secondary
            @else bg-info @endif">
            {{ ucfirst($order->status) }}
        </span>
    </p>
    <p><strong>Total:</strong> Rp{{ number_format($order->total_price, 0, ',', '.') }}</p>
    <p><strong>Dibuat pada:</strong> {{ $order->created_at->format('d M Y H:i') }}</p>
</div>

<h4>Items</h4>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>Produk</th>
                <th>Harga</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>Rp{{ number_format($item->price, 0, ',', '.') }}</td>
                <td>{{ $item->quantity }}</td>
                <td>Rp{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ✅ Tombol Bayar & Cancel hanya jika unpaid --}}
@if($order->status === 'unpaid')
    <div class="d-flex gap-2 mt-3">
        {{-- Bayar --}}
        <form action="{{ route('buyer.checkout.pay', $order->id) }}" method="POST" id="payForm">
            @csrf
            <button type="button" class="btn btn-lg btn-success" id="payBtn">
                <i class="bi bi-cash-coin"></i> Bayar Sekarang
            </button>
        </form>

        {{-- Cancel --}}
        <form action="{{ route('buyer.orders.cancel', $order->id) }}" method="POST" id="cancelForm">
            @csrf
            @method('PATCH')
            <button type="button" class="btn btn-lg btn-outline-danger" id="cancelBtn">
                <i class="bi bi-x-circle"></i> Batalkan Pesanan
            </button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Konfirmasi Bayar
    const payBtn = document.getElementById('payBtn');
    const payForm = document.getElementById('payForm');
    if (payBtn && payForm) {
        payBtn.addEventListener('click', () => {
            Swal.fire({
                title: "Konfirmasi Pembayaran",
                text: "Apakah kamu yakin ingin membayar order ini?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, bayar!",
                cancelButtonText: "Batal",
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#6c757d"
            }).then((result) => {
                if (result.isConfirmed) {
                    payForm.submit();
                }
            });
        });
    }

    // Konfirmasi Cancel
    const cancelBtn = document.getElementById('cancelBtn');
    const cancelForm = document.getElementById('cancelForm');
    if (cancelBtn && cancelForm) {
        cancelBtn.addEventListener('click', () => {
            Swal.fire({
                title: "Batalkan Pesanan?",
                text: "Order ini akan dibatalkan dan stok produk dikembalikan.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, batalkan!",
                cancelButtonText: "Tidak",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d"
            }).then((result) => {
                if (result.isConfirmed) {
                    cancelForm.submit();
                }
            });
        });
    }
});
</script>
@endpush
