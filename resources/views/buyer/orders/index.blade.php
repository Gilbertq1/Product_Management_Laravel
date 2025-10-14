@extends('layouts.buyer')

@section('title', 'Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">My Orders</h2>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Status</th>
                <th>Total</th>
                <th>Created At</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td><strong>#{{ $order->id }}</strong></td>
                <td>
                    <span class="badge 
                        @if($order->status === 'paid') bg-success 
                        @elseif($order->status === 'unpaid') bg-warning text-dark
                        @elseif($order->status === 'cancelled') bg-danger
                        @elseif($order->status === 'expired') bg-secondary
                        @else bg-info @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td>Rp{{ number_format($order->total_price, 0, ',', '.') }}</td>
                <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                <td class="text-center">
                    <a href="{{ route('buyer.orders.show', $order->id) }}" 
                       class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-eye"></i> Detail
                    </a>

                    @if($order->status === 'unpaid')
                        {{-- Tombol Bayar --}}
                        <form action="{{ route('buyer.orders.pay', $order->id) }}" method="POST" 
                              id="payForm-{{ $order->id }}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-sm btn-success payBtn" 
                                    data-id="{{ $order->id }}">
                                <i class="bi bi-cash-coin"></i> Bayar
                            </button>
                        </form>

                        {{-- Tombol Cancel --}}
                        <form action="{{ route('buyer.orders.cancel', $order->id) }}" method="POST" 
                              id="cancelForm-{{ $order->id }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="button" class="btn btn-sm btn-outline-danger cancelBtn" 
                                    data-id="{{ $order->id }}">
                                <i class="bi bi-x-circle"></i> Batal
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">Belum ada order.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Konfirmasi Bayar
    document.querySelectorAll(".payBtn").forEach(btn => {
        btn.addEventListener("click", () => {
            let id = btn.dataset.id;
            Swal.fire({
                title: "Konfirmasi Pembayaran",
                text: "Apakah kamu yakin ingin membayar order #" + id + "?",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, bayar!",
                cancelButtonText: "Batal",
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#6c757d"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("payForm-" + id).submit();
                }
            });
        });
    });

    // Konfirmasi Cancel
    document.querySelectorAll(".cancelBtn").forEach(btn => {
        btn.addEventListener("click", () => {
            let id = btn.dataset.id;
            Swal.fire({
                title: "Batalkan Pesanan?",
                text: "Order #" + id + " akan dibatalkan dan stok produk dikembalikan.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, batalkan!",
                cancelButtonText: "Tidak",
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("cancelForm-" + id).submit();
                }
            });
        });
    });
});
</script>
@endpush