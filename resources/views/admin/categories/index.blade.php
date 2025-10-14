@extends('adminlte::page')

@section('title', 'Manage Categories')

@section('content_header')
    <h1>Manage Categories</h1>
@stop

@section('content')
    {{-- Tombol tambah hanya di Active --}}
    @if(request('status') !== 'trash')
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">
            <i class="fas fa-plus"></i> Tambah Category
        </a>
    @endif

    {{-- 🔄 Tabs: Active vs Trash --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request('status') !== 'trash' ? 'active' : '' }}"
               href="{{ route('admin.categories.index') }}">
                Active
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') === 'trash' ? 'active' : '' }}"
               href="{{ route('admin.categories.index', ['status' => 'trash']) }}">
                Trash
            </a>
        </li>
    </ul>

    {{-- 🔍 Filter --}}
    <form method="GET" action="{{ route('admin.categories.index') }}" class="form-inline mb-3">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="text" name="search" value="{{ request('search') }}"
               class="form-control mr-2" placeholder="Cari kategori...">
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>

    {{-- ✅ Bulk Action --}}
    <form method="POST" action="{{ route('admin.categories.bulkAction') }}" id="bulk-form-categories" class="mb-3">
        @csrf
        <div class="form-inline">
            <select name="action" class="form-control mr-2" required>
                <option value="">-- Bulk Action --</option>
                @if(request('status') === 'trash')
                    <option value="restore">Restore</option>
                    <option value="force_delete">Hapus Permanen</option>
                @else
                    <option value="delete">Pindah ke Trash</option>
                @endif
            </select>
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>

    {{-- 📋 Categories Table --}}
    <table class="table table-bordered table-hover">
        <thead class="thead-light">
            <tr>
                <th><input type="checkbox" id="select-all-categories"></th>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th width="150">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $key => $cat)
                <tr>
                    <td><input type="checkbox" class="row-checkbox-category" value="{{ $cat->id }}"></td>
                    <td>{{ $categories->firstItem() + $key }}</td>
                    <td>{{ $cat->name }}</td>
                    <td>{{ $cat->slug }}</td>
                    <td>{{ Str::limit($cat->description, 50) ?? '-' }}</td>
                    <td>
                        @if(request('status') === 'trash')
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown">
                                    Actions
                                </button>
                                <div class="dropdown-menu">
                                    <form action="{{ route('admin.categories.restore', $cat->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.categories.forceDelete', $cat->id) }}" method="POST" class="m-0 single-delete-category">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-times"></i> Hapus Permanen
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
                                    <a class="dropdown-item" href="{{ route('admin.categories.edit', $cat) }}">
                                        <i class="fas fa-edit text-warning"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="m-0 single-delete-category">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-trash"></i> Trash
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Tidak ada category ditemukan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $categories->withQueryString()->links() }}
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Select/Deselect all
    document.getElementById('select-all-categories').addEventListener('click', function() {
        document.querySelectorAll('.row-checkbox-category').forEach(cb => cb.checked = this.checked);
    });

    // Confirm delete / force delete
    document.querySelectorAll('.single-delete-category').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Yakin?",
                text: "{{ request('status') === 'trash' ? 'Kategori akan dihapus permanen!' : 'Kategori akan dipindahkan ke trash!' }}",
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

    // Bulk Action
    document.getElementById('bulk-form-categories').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = this;
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

        let checked = document.querySelectorAll('.row-checkbox-category:checked');
        if (checked.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: 'Pilih minimal satu kategori dulu sebelum apply bulk action.',
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
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}" });
    @endif

    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Oops!', text: "{{ session('error') }}" });
    @endif
</script>
@stop
