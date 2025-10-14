@extends('layouts.buyer')

@section('title', 'Daftar Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">🛍️ Daftar Produk</h2>
    <a href="{{ route('buyer.cart.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-cart3"></i> Lihat Keranjang
    </a>
</div>

{{-- 🔍 Filter & Search --}}
<div class="card shadow-sm mb-4 border-0">
    <div class="card-body">
        <form method="GET" action="{{ route('buyer.products.index') }}" id="filterForm" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control"
                    placeholder="Cari produk..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="min_price" class="form-control"
                    placeholder="Harga min" value="{{ request('min_price') }}">
            </div>
            <div class="col-md-2">
                <input type="number" name="max_price" class="form-control"
                    placeholder="Harga max" value="{{ request('max_price') }}">
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="">Urutkan</option>
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Murah → Mahal</option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Mahal → Murah</option>
                </select>
            </div>
        </form>
    </div>
</div>

{{-- 🔹 List Produk --}}
<div id="productList">
    @include('buyer.products.partials.product-list', ['products' => $products])
</div>
@endsection

@push('styles')
<style>
    .image-wrapper {
        width: 100%;
        height: 200px;
        background: #f8f9fa;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        border-top-left-radius: .5rem;
        border-top-right-radius: .5rem;
        position: relative;
    }
    .product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function($){
    function debounce(fn, delay = 400) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // gabungkan form params ke URL (aman: set() overrides, delete empty)
    function buildFetchUrl(url = null) {
        let base = url ?? "{{ route('buyer.products.liveSearch') }}";
        let u = new URL(base, window.location.origin);

        // ambil semua form fields
        $('#filterForm').serializeArray().forEach(function(item) {
            if (item.value !== '') {
                u.searchParams.set(item.name, item.value);
            } else {
                u.searchParams.delete(item.name);
            }
        });

        return u.toString(); // full absolute url
    }

    function fetchProducts(url = null) {
        let fullUrl = buildFetchUrl(url);

        $.ajax({
            url: fullUrl,
            method: "GET",
            beforeSend: function() {
                $('#productList').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></div>');
            },
            success: function (data) {
                $('#productList').html(data);
            },
            error: function (xhr, status, err) {
                console.error('AJAX ERROR', status, err);
                console.error(xhr.responseText);
                $('#productList').html('<div class="alert alert-danger text-center p-5">Gagal memuat produk. Cek console (F12 → Network / Console).</div>');
            }
        });
    }

    $(document).ready(function () {
        // live search debounce
        $("input[name='search']").on('keyup', debounce(function() {
            fetchProducts();
        }, 400));

        // other filters
        $("#filterForm select, #filterForm input[name='min_price'], #filterForm input[name='max_price']").on('change', function(){
            fetchProducts();
        });

        // pagination click handler (works because partial's links point to liveSearch path)
        $(document).on('click', '#productList .pagination a', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            fetchProducts(href);
        });
    });
})(jQuery);
</script>
@endpush
