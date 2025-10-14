{{-- resources/views/seller/products/show.blade.php --}}
@extends('adminlte::page')

@section('title', 'Detail Produk')

@section('content_header')
    <h1>Detail Produk</h1>
@stop

@section('content')
<div class="card shadow-sm border-0">
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
                        <img src="https://via.placeholder.com/400x300?text=No+Thumbnail"
                             class="img-fluid rounded shadow-sm w-100"
                             style="max-height: 350px; object-fit: cover;">
                    @endif
                </div>

                {{-- Gambar lainnya --}}
                @if($others->count())
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($others as $img)
                            <div class="position-relative" style="width: 80px; height: 80px;">
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

                {{-- Harga dengan final_price --}}
                @if($product->final_price < $product->price)
                    <p>
                        <strong>Harga:</strong>
                        <span class="text-danger fw-bold">
                            Rp {{ number_format($product->final_price, 0, ',', '.') }}
                        </span>
                        <small class="text-muted text-decoration-line-through">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </small>
                    </p>
                @else
                    <p><strong>Harga:</strong> Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                @endif

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

                {{-- 🔘 Aksi --}}
                <a href="{{ route('seller.products.edit', $product) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('seller.products.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>
@stop
