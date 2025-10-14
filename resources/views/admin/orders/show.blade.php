@extends('adminlte::page')

@section('title', 'Detail Order')

@section('content_header')
    <h1>Detail Order #{{ $order->order_code ?? $order->id }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h5>Informasi Order</h5>
            <ul class="list-group mb-3">
                <li class="list-group-item"><b>Buyer:</b> {{ $order->user?->name ?? '-' }}</li>
                <li class="list-group-item"><b>Status:</b> 
                    <span class="badge bg-{{ $order->status == 'unpaid' ? 'danger' : ($order->status == 'paid' ? 'success' : ($order->status == 'shipped' ? 'info' : 'secondary')) }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </li>
                <li class="list-group-item"><b>Total:</b> Rp {{ number_format($order->total_price, 0, ',', '.') }}</li>
                <li class="list-group-item"><b>Dibuat:</b> {{ $order->created_at->format('d M Y H:i') }}</li>
            </ul>

            <h5>Items</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $i => $item)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $item->product?->name ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada item</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
@stop
