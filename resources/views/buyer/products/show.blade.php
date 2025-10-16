@extends('layouts.buyer')

@section('title', $product->name)

@section('content')
<div class="container my-5">
    <div class="row g-4">
        {{-- LEFT: IMAGE GALLERY / SLIDER --}}
        <div class="col-md-6">
            @php
                $images = $product->images;
            @endphp

            @if($images->count())
            <div id="productCarousel" class="carousel slide border rounded shadow-sm" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach($images as $i => $img)
                    <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                        <img src="{{ asset('storage/'.$img->image_path) }}"
                             alt="{{ $product->name }} image {{ $i+1 }}"
                             class="d-block w-100"
                             style="max-height: 400px; object-fit: contain;">
                    </div>
                    @endforeach
                </div>

                {{-- controls --}}
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>

                {{-- indicators as thumbnail buttons --}}
                <div class="carousel-indicators mt-3 d-flex gap-2 flex-wrap">
                    @foreach($images as $i => $img)
                    <button type="button"
                            data-bs-target="#productCarousel"
                            data-bs-slide-to="{{ $i }}"
                            class="p-0 border rounded {{ $i === 0 ? 'active' : '' }} indicator-thumb"
                            aria-label="Slide {{ $i + 1 }}"
                            style="width:60px; height:60px; overflow:hidden; border:1px solid #ddd;">
                        <img src="{{ asset('storage/'.$img->image_path) }}"
                             class="w-100 h-100"
                             alt="Thumbnail {{ $i + 1 }}"
                             style="object-fit: cover;">
                    </button>
                    @endforeach
                </div>
            </div>
            @else
            <img src="https://via.placeholder.com/500x400?text=No+Image"
                 class="img-fluid rounded border shadow-sm w-100"
                 style="max-height: 400px; object-fit: contain;"
                 alt="No image available">
            @endif
        </div>

        {{-- RIGHT: PRODUCT INFO --}}
        <div class="col-md-6">
            <h2 class="fw-bold mb-3">{{ $product->name }}</h2>

            {{-- price --}}
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
                <span class="badge bg-info text-dark">{{ $cat->name }}</span>
                @empty
                <span class="text-muted">-</span>
                @endforelse
            </p>

            <p class="mb-4 text-muted">{{ $product->description ?? '-' }}</p>

            {{-- action buttons --}}
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
                             style="height:160px; object-fit:cover;"
                             alt="{{ $rel->name }}">
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

{{-- small inline styles for indicator thumbs (move to main CSS if desired) --}}
<style>
    .carousel-indicators .indicator-thumb.active {
        box-shadow: 0 0 0 3px rgba(13,110,253,.15);
    }
    .carousel-indicators .indicator-thumb img {
        display: block;
    }
</style>

{{-- script to ensure thumbnails control the carousel using Bootstrap's API --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var carouselEl = document.getElementById('productCarousel');
        if (!carouselEl) return;

        // Initialize Carousel (Bootstrap 5) instance if not auto-initialized
        var carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl);

        // Attach click event to indicator thumbs so they behave reliably
        document.querySelectorAll('#productCarousel .carousel-indicators button').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                // data-bs-slide-to is zero-based index
                var index = parseInt(this.getAttribute('data-bs-slide-to'), 10);
                if (!isNaN(index)) {
                    carousel.to(index);
                }
            });
        });
    });
</script>
@endsection
