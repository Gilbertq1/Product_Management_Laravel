@extends('adminlte::page')

@section('title', 'Produk Saya')

@section('content_header')
<h1>Produk Saya</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Daftar Produk</h3>
        <div>
            {{-- Export --}}
            <a href="{{ route('seller.products.export') }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export
            </a>

            {{-- Import --}}
            <a href="{{ route('seller.products.importForm') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-file-upload"></i> Import
            </a>

            {{-- Tambah Produk --}}
            <a href="{{ route('seller.products.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Produk
            </a>
        </div>
    </div>

    <div class="card-body">

        {{-- 🔄 Tabs: Active vs Trash --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request('status') !== 'trash' ? 'active' : '' }}"
                    href="{{ route('seller.products.index') }}">
                    Active
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') === 'trash' ? 'active' : '' }}"
                    href="{{ route('seller.products.index', ['status' => 'trash']) }}">
                    Trash
                </a>
            </li>
        </ul>

        {{-- 🔍 Filter Form --}}
        <form method="GET" action="{{ route('seller.products.index') }}" class="form-inline mb-3">
            <input type="hidden" name="status" value="{{ request('status') }}">

            <input type="text" name="search" value="{{ request('search') }}"
                class="form-control mr-2" placeholder="Cari produk...">

            <select name="category" class="form-control mr-2">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category')==$cat->id?'selected':'' }}>
                    {{ $cat->name }}
                </option>
                @endforeach
            </select>

            <select name="sort" class="form-control mr-2">
                <option value="">Urutkan</option>
                <option value="price_asc" {{ request('sort')=='price_asc'?'selected':'' }}>Harga Termurah</option>
                <option value="price_desc" {{ request('sort')=='price_desc'?'selected':'' }}>Harga Termahal</option>
                <option value="stock_asc" {{ request('sort')=='stock_asc'?'selected':'' }}>Stok Sedikit</option>
                <option value="stock_desc" {{ request('sort')=='stock_desc'?'selected':'' }}>Stok Banyak</option>
                <option value="best_seller" {{ request('sort')=='best_seller'?'selected':'' }}>Terlaris</option>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>

        {{-- ✅ Bulk Action --}}
        <form method="POST" action="{{ route('seller.products.bulkAction') }}" id="bulk-form-products" class="mb-3">
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

        {{-- 📋 Products Table --}}
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th><input type="checkbox" id="select-all-products"></th>
                    <th>Thumbnail</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Terjual</th>
                    <th>Status</th>
                    <th width="160">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                <tr>
                    <td><input type="checkbox" class="row-checkbox-product" value="{{ $p->id }}"></td>
                    <td>
                        @if($p->thumbnail_url)
                        <img src="{{ asset($p->thumbnail_url) }}"
                            alt="{{ $p->name }}"
                            width="60" height="60"
                            style="object-fit: cover; border-radius: 6px;">
                        @else
                        <span class="text-muted">No Image</span>
                        @endif
                    </td>
                    <td>{{ $p->name }}</td>
                    <td>
                        @if($p->categories->count())
                        @foreach($p->categories as $cat)
                        <span class="badge badge-info">{{ $cat->name }}</span>
                        @endforeach
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                    <td>{{ $p->stock }}</td>
                    <td>{{ $p->total_sold }}</td>
                    <td>
                        @if($p->status)
                        <span class="badge badge-success">Active</span>
                        @else
                        <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if(request('status') === 'trash')
                        {{-- Tombol restore --}}
                        <form action="{{ route('seller.products.restore', $p->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-undo"></i>
                            </button>
                        </form>

                        {{-- Tombol force delete --}}
                        <form action="{{ route('seller.products.forceDelete', $p->id) }}" method="POST" class="single-delete-product" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @else
                        {{-- Show & edit --}}
                        <a href="{{ route('seller.products.show', $p) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('seller.products.edit', $p) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                        </a>

                        {{-- Soft delete --}}
                        <form action="{{ route('seller.products.destroy', $p) }}" method="POST" class="single-delete-product" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">Belum ada produk</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $products->withQueryString()->links() }}
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Select / Deselect all
    document.getElementById('select-all-products').addEventListener('click', function() {
        document.querySelectorAll('.row-checkbox-product').forEach(cb => cb.checked = this.checked);
    });

    // Confirm delete (trash / force delete)
    document.querySelectorAll('.single-delete-product').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Yakin?",
                text: "{{ request('status') === 'trash' ? 'Produk akan dihapus permanen!' : 'Produk akan dipindahkan ke trash!' }}",
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
    document.getElementById('bulk-form-products').addEventListener('submit', function(e) {
        e.preventDefault();
        let form = this;
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

        let checked = document.querySelectorAll('.row-checkbox-product:checked');
        if (checked.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: 'Pilih minimal satu produk dulu sebelum apply bulk action.',
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