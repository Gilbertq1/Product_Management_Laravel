@extends('adminlte::page')

@section('title', 'Manage Users')

@section('content_header')
<h1>Manage Users</h1>
@stop

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah User
        </a>
    </div>
    <div>
        {{-- 🔄 Import & Export --}}
        <a href="{{ route('admin.users.export') }}" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export
        </a>
        <a href="{{ route('admin.users.import.form') }}" class="btn btn-info">
            <i class="fas fa-file-import"></i> Import
        </a>
    </div>
</div>

{{-- 🔄 Tabs: Active vs Trash --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request('status') !== 'trash' ? 'active' : '' }}"
            href="{{ route('admin.users.index') }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request('status') === 'trash' ? 'active' : '' }}"
            href="{{ route('admin.users.index', ['status' => 'trash']) }}">Trash</a>
    </li>
</ul>

{{-- 🔍 Filter (Search + Sort) --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="form-inline mb-3">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="text" name="search" value="{{ request('search') }}"
        class="form-control mr-2" placeholder="Cari nama / email...">
    <select name="sort" class="form-control mr-2" onchange="this.form.submit()">
        <option value="">Urutkan</option>
        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama A-Z</option>
        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama Z-A</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
</form>

{{-- ✅ Bulk Action --}}
<form method="POST" action="{{ route('admin.users.bulkAction') }}" id="bulk-form-users" class="mb-3">
    @csrf
    <div class="form-inline">
        <select name="action" class="form-control mr-2" required>
            <option value="">-- Bulk Action --</option>
            @if(request('status') === 'trash')
            <option value="restore">Restore</option>
            <option value="force_delete">Hapus Permanen</option>
            @else
            <option value="delete">Pindah ke Trash</option>
            <option value="activate">Set Active</option>
            <option value="deactivate">Set Inactive</option>
            @endif
        </select>
        <button type="submit" class="btn btn-primary">Apply</button>
    </div>
</form>

{{-- 📋 Users Table --}}
<table class="table table-bordered table-hover">
    <thead class="thead-light">
        <tr>
            <th><input type="checkbox" id="select-all-users"></th>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Balance</th>
            <th width="150">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $key => $user)
        <tr>
            <td><input type="checkbox" class="row-checkbox-user" value="{{ $user->id }}"></td>
            <td>{{ $users->firstItem() + $key }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td><span class="badge bg-secondary">{{ $user->role }}</span></td>
            <td>Rp {{ number_format($user->balance, 2, ',', '.') }}</td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button"
                        id="dropdownMenuButton{{ $user->id }}" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        Actions
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton{{ $user->id }}">
                        @if(request('status') === 'trash')
                        {{-- Restore --}}
                        <form action="{{ route('admin.users.restore', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-success">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </form>
                        {{-- Force Delete --}}
                        <form action="{{ route('admin.users.forceDelete', $user->id) }}" method="POST" class="single-delete-user">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-times"></i> Hapus Permanen
                            </button>
                        </form>
                        @else
                        {{-- Edit --}}
                        <a href="{{ route('admin.users.edit', $user) }}" class="dropdown-item text-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        {{-- Soft Delete --}}
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="single-delete-user">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash"></i> Trash
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted">Belum ada user ditemukan</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Pagination --}}
<div class="mt-3">
    {{ $users->withQueryString()->links() }}
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ✅ Select/Deselect all
    document.getElementById('select-all-users').addEventListener('click', function() {
        document.querySelectorAll('.row-checkbox-user').forEach(cb => cb.checked = this.checked);
    });

    // ✅ Confirm delete / force delete
    document.querySelectorAll('.single-delete-user').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Yakin?",
                text: "{{ request('status') === 'trash' ? 'User akan dihapus permanen!' : 'User akan dipindahkan ke trash!' }}",
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

    // ✅ Bulk Action
    document.getElementById('bulk-form-users').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = this;
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

        let checked = document.querySelectorAll('.row-checkbox-user:checked');
        if (checked.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: 'Pilih minimal satu user dulu sebelum apply bulk action.',
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

    // ✅ Flash Messages
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