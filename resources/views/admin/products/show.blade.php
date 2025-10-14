{{-- resources/views/admin/products/show.blade.php --}}
@extends('adminlte::page')

@section('title', 'Detail Produk')

@section('content_header')
    <h1>Detail Produk</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row">
            {{-- 📸 Semua Gambar Produk --}}
            <div class="col-md-5">
                @php
                    $thumbnail = $product->images->where('is_thumbnail', true)->first();
                    $others    = $product->images->where('is_thumbnail', false);
                @endphp

                {{-- Gambar utama (thumbnail) --}}
                <div class="mb-3">
                    @if($thumbnail)
                        <img src="{{ asset('storage/'.$thumbnail->image_path) }}" 
                             alt="Thumbnail {{ $product->name }}" 
                             class="img-fluid rounded shadow-sm w-100"
                             style="max-height: 350px; object-fit: cover;">
                    @else
                        <div class="text-muted">Tidak ada thumbnail</div>
                    @endif
                </div>

                {{-- Gambar lainnya --}}
                @if($others->count())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($others as $img)
                            <div class="position-relative mr-2 mb-2" style="width: 80px; height: 80px;">
                                <img src="{{ asset('storage/'.$img->image_path) }}" 
                                     class="img-thumbnail w-100 h-100"
                                     style="object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 📝 Detail Produk --}}
            <div class="col-md-7">
                <h3 class="mb-3">{{ $product->name }}</h3>
                <p>{{ $product->description ?? '-' }}</p>

                {{-- Harga --}}
                <p><strong>Harga:</strong> Rp {{ number_format($product->price, 0, ',', '.') }}</p>

                <p><strong>Stok:</strong> {{ $product->stock }}</p>
                <p><strong>Status:</strong>
                    @if($product->status)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </p>
                <p><strong>Kategori:</strong>
                    @forelse($product->categories as $cat)
                        <span class="badge bg-info">{{ $cat->name }}</span>
                    @empty
                        <span class="text-muted">-</span>
                    @endforelse
                </p>
                <p><strong>Total Terjual:</strong> {{ $product->total_sold ?? 0 }}</p>
                <p><strong>Seller:</strong> {{ $product->seller?->name ?? '-' }}</p>

                {{-- 🔘 Aksi --}}
                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@stop
