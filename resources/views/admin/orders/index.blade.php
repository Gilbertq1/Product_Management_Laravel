@extends('adminlte::page')

@section('title', 'Manage Orders')

@section('content_header')
<h1>Manage Orders</h1>
@stop

@section('content')
<a href="{{ route('admin.orders.create') }}" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> Tambah Order
</a>

{{-- 🔄 Tabs --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request('status_view') !== 'trash' ? 'active' : '' }}"
            href="{{ route('admin.orders.index') }}">
            Active
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('status_view') === 'trash' ? 'active' : '' }}"
            href="{{ route('admin.orders.index', ['status_view' => 'trash']) }}">
            Trash
        </a>
    </li>
</ul>


{{-- 🔍 Filter --}}
<form method="GET" action="{{ route('admin.orders.index') }}" class="form-inline mb-3">
    {{-- bawa param status_view supaya tab tetap --}}
    <input type="hidden" name="status_view" value="{{ request('status_view') }}">

    <input type="text" name="search" class="form-control mr-2"
        placeholder="Cari order code / buyer..."
        value="{{ request('search') }}">

    <input type="date" name="date_from" class="form-control mr-2"
        value="{{ request('date_from') }}">
    <input type="date" name="date_to" class="form-control mr-2"
        value="{{ request('date_to') }}">

    <select name="status" class="form-control mr-2">
        <option value="">Semua Status</option>
        <option value="unpaid" {{ request('status')=='unpaid' ? 'selected' : '' }}>Unpaid</option>
        <option value="paid" {{ request('status')=='paid' ? 'selected' : '' }}>Paid</option>
        <option value="shipped" {{ request('status')=='shipped' ? 'selected' : '' }}>Shipped</option>
        <option value="done" {{ request('status')=='done' ? 'selected' : '' }}>Done</option>
    </select>

    <select name="sort" class="form-control mr-2">
        <option value="">Urutkan</option>
        <option value="newest" {{ request('sort')=='newest' ? 'selected' : '' }}>Terbaru</option>
        <option value="oldest" {{ request('sort')=='oldest' ? 'selected' : '' }}>Terlama</option>
        <option value="amount_desc" {{ request('sort')=='amount_desc' ? 'selected' : '' }}>Nominal: Besar → Kecil</option>
        <option value="amount_asc" {{ request('sort')=='amount_asc' ? 'selected' : '' }}>Nominal: Kecil → Besar</option>
    </select>

    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

{{-- ✅ Bulk Action --}}
<form method="POST" action="{{ route('admin.orders.bulkAction') }}" id="bulk-form-orders" class="mb-3">
    @csrf
    <div class="form-inline">
        <select name="action" class="form-control mr-2" required>
            <option value="">-- Bulk Action --</option>
            @if(request('status_view') === 'trash')
            <option value="restore">Restore</option>
            <option value="force_delete">Delete Permanently</option>
            @else
            <option value="delete">Move to Trash</option>
            <option value="mark_paid">Mark as Paid</option>
            <option value="mark_shipped">Mark as Shipped</option>
            <option value="mark_done">Mark as Done</option>
            @endif
        </select>
        <button type="submit" class="btn btn-primary">Apply</button>
    </div>
</form>

{{-- 📋 Orders Table --}}
<table class="table table-bordered table-hover">
    <thead class="thead-light">
        <tr>
            <th><input type="checkbox" id="select-all-orders"></th>
            <th>#</th>
            <th>Order Code</th>
            <th>Buyer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Created</th>
            <th width="200">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($orders as $key => $order)
        <tr>
            <td><input type="checkbox" class="row-checkbox-order" value="{{ $order->id }}"></td>
            <td>{{ $orders->firstItem() + $key }}</td>
            <td>{{ $order->order_code ?? ('#' . $order->id) }}</td>
            <td>{{ $order->user?->name ?? '-' }}</td>
            <td>{{ $order->items_count }}</td>
            <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
            <td>
                @php
                $map = [
                'unpaid' => 'badge-danger',
                'paid' => 'badge-success',
                'shipped' => 'badge-info',
                'done' => 'badge-secondary',
                ];
                @endphp
                <span class="badge {{ $map[$order->status] ?? 'badge-dark' }}">
                    {{ ucfirst($order->status) }}
                </span>
            </td>
            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
            <td>
                @if(request('status_view') === 'trash')
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                        Actions
                    </button>
                    <div class="dropdown-menu">
                        {{-- Restore --}}
                        <form action="{{ route('admin.orders.restore', $order->id) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-success">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </form>

                        {{-- Force Delete --}}
                        <form action="{{ route('admin.orders.forceDelete', $order->id) }}" method="POST" class="m-0 single-delete-order">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash"></i> Delete Permanently
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                        Actions
                    </button>
                    <div class="dropdown-menu">
                        @if($order->status === 'unpaid')
                        <form action="{{ route('admin.orders.pay', $order) }}" method="POST" class="m-0 pay-form">
                            @csrf @method('PUT')
                            <button type="submit" class="dropdown-item text-success">
                                <i class="fas fa-money-bill"></i> Mark Paid
                            </button>
                        </form>
                        @endif

                        <a class="dropdown-item" href="{{ route('admin.orders.show', $order) }}">
                            <i class="fas fa-eye text-info"></i> Lihat
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.orders.edit', $order) }}">
                            <i class="fas fa-edit text-warning"></i> Edit
                        </a>
                        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" class="m-0 single-delete-order">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center text-muted">No orders found</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Pagination --}}
<div class="mt-3">
    {{ $orders->withQueryString()->links() }}
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Select / Deselect all
    document.getElementById('select-all-orders').addEventListener('click', function() {
        document.querySelectorAll('.row-checkbox-order').forEach(cb => cb.checked = this.checked);
    });

    // Confirm delete (trash / force delete)
    document.querySelectorAll('.single-delete-order').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Yakin?",
                text: "{{ request('status_view') === 'trash' ? 'Order akan dihapus permanen!' : 'Order akan dipindahkan ke trash!' }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: "Ya, lanjutkan!"
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // Confirm mark paid
    document.querySelectorAll('.pay-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Mark as Paid?",
                text: "Order ini akan ditandai sebagai PAID",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "Ya, tandai!"
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    // Bulk Action
    document.getElementById('bulk-form-orders').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = this;
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

        let checked = document.querySelectorAll('.row-checkbox-order:checked');
        if (checked.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: 'Pilih minimal satu order dulu sebelum apply bulk action.',
            });
            return;
        }

        checked.forEach(cb => {
            let hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'ids[]';
            hidden.value = cb.value;
            form.appendChild(hidden);
        });

        form.submit();
    });

    // Flash Messages
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: "{{ session('success') }}"
    });
    @endif

    @if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: "{{ session('error') }}"
    });
    @endif
</script>
@stop