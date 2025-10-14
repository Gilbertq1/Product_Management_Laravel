@extends('adminlte::page')

@section('title', 'Activity Logs')

@section('content_header')
    <h1>Activity Logs</h1>
@stop

@section('content')
<div class="card">
    {{-- 🔍 Filter Bar (sama gaya dengan halaman produk) --}}
    <div class="card-body border-bottom pb-2 mb-2">
        <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="form-inline mb-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control mr-2" placeholder="Cari aktivitas...">

            <select name="role" class="form-control mr-2">
                <option value="all">Semua Role</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="seller" {{ request('role') == 'seller' ? 'selected' : '' }}>Seller</option>
                <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
            </select>

            <select name="action" class="form-control mr-2">
                <option value="all">Semua Aksi</option>
                <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
            </select>

            <input type="date" name="date" value="{{ request('date') }}" class="form-control mr-2">

            <button type="submit" class="btn btn-secondary mr-2">
                <i class="fas fa-filter"></i> Filter
            </button>

            <a href="{{ route('admin.activity_logs.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-undo"></i> Reset
            </a>
        </form>
    </div>

    {{-- 📋 Activity Logs Table --}}
    <div class="card-body table-responsive">
        <table class="table table-hover table-bordered table-striped align-middle">
            <thead class="thead-dark">
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->user->name ?? 'System' }}</td>
                        <td>{{ $log->user->role ?? '-' }}</td>
                        <td>
                            @php
                                $badgeClass = match($log->action) {
                                    'create' => 'success',
                                    'update' => 'warning',
                                    'delete' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge badge-{{ $badgeClass }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</div>
@stop