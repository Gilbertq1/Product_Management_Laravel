@extends('layouts.buyer')

@section('title', $product->name)

@section('content')
<div class="container my-5">
    <div class="row g-4">
        {{-- LEFT: IMAGE GALLERY --}}
        {{-- LEFT: IMAGE SLIDER --}}
        <div class="col-md-6">
            @php
            $images = $product->images;
            @endphp

            @if($images->count())
            <div id="productCarousel" class="carousel slide border rounded shadow-sm" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($images as $i => $img)
                    <div class="carousel-item {{ $i == 0 ? 'active' : '' }}">
                        <img src="{{ asset('storage/'.$img->image_path) }}"
                            class="d-block w-100"
                            style="max-height: 400px; object-fit: contain;">
                    </div>
                    @endforeach
                </div>

                {{-- kontrol kiri kanan --}}
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>

                {{-- indikator (dot bawah) --}}
                <div class="carousel-indicators">
                    @foreach($images as $i => $img)
                    <button type="button"
                        data-bs-target="#productCarousel"
                        data-bs-slide-to="{{ $i }}"
                        class="{{ $i == 0 ? 'active' : '' }}"
                        aria-current="{{ $i == 0 ? 'true' : 'false' }}"
                        style="width:60px; height:60px; overflow:hidden; border:1px solid #ddd;">
                        <img src="{{ asset('storage/'.$img->image_path) }}"
                            class="w-100 h-100"
                            style="object-fit: cover;">
                    </button>
                    @endforeach
                </div>
            </div>
            @else
            <img src="https://via.placeholder.com/500x400?text=No+Image"
                class="img-fluid rounded border shadow-sm w-100"
                style="max-height: 400px; object-fit: contain;">
            @endif
        </div>


        {{-- RIGHT: PRODUCT INFO --}}
        <div class="col-md-6">
            <h2 class="fw-bold mb-3">{{ $product->name }}</h2>

            {{-- harga --}}
            @if($product->final_price < $product->price)
                <div class="mb-2">
                    <span class="fs-3 fw-bold text-danger">
                        Rp{{ number_format($product->final_price, 0, ',', '.') }}
                    </span>
                    <span class="text-muted text-decoration-line-through ms-2">
                        Rp{{ number_format($product->price, 0, ',', '.') }}
                    </span>
                </div>
                @else
                <div class="mb-2">
                    <span class="fs-3 fw-bold text-primary">
                        Rp{{ number_format($product->price, 0, ',', '.') }}
                    </span>
                </div>
                @endif

                <p class="mb-2"><strong>Stok:</strong> {{ $product->stock }}</p>
                <p class="mb-2"><strong>Kategori:</strong>
                    @forelse($product->categories as $cat)
                    <span class="badge bg-info">{{ $cat->name }}</span>
                    @empty
                    <span class="text-muted">-</span>
                    @endforelse
                </p>

                <p class="mb-4 text-muted">{{ $product->description ?? '-' }}</p>

                {{-- tombol aksi --}}
                <div class="d-flex gap-3">
                    @if($product->stock > 0)
                    <a href="{{ route('buyer.cart.add', $product->id) }}" class="btn btn-lg btn-success flex-grow-1">
                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                    </a>
                    @else
                    <button class="btn btn-lg btn-secondary flex-grow-1" disabled>Stok Habis</button>
                    @endif
                    <a href="{{ route('buyer.products.index') }}" class="btn btn-lg btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
        </div>
    </div>

    {{-- RELATED / RECOMMENDED --}}
    <div class="mt-5">
        <h4 class="fw-bold mb-3">Produk Lainnya</h4>
        <div class="row">
            @foreach($related as $rel)
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="image-wrapper">
                        @php $thumb = $rel->images->where('is_thumbnail', true)->first(); @endphp
                        <img src="{{ $thumb ? asset('storage/'.$thumb->image_path) : 'https://via.placeholder.com/200x150?text=No+Image' }}"
                            class="card-img-top"
                            style="height:160px; object-fit:cover;">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold">{{ $rel->name }}</h6>
                        <span class="text-danger fw-bold mb-2">Rp{{ number_format($rel->final_price,0,',','.') }}</span>
                        <a href="{{ route('buyer.products.show', $rel->id) }}" class="btn btn-sm btn-outline-primary mt-auto">
                            Lihat
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- script untuk klik thumbnail --}}
<script>
    document.querySelectorAll('.thumb-img').forEach(img => {
        img.addEventListener('click', function() {
            document.getElementById('mainImage').src = this.src;
            document.querySelectorAll('.thumb-img').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
</script>
@endsection