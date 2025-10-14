<div class="row">
    @forelse($products as $product)
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="image-wrapper">
                @php
                $thumb = $product->images->where('is_thumbnail', true)->first();
                @endphp

                @if($thumb)
                <img src="{{ asset('storage/' . $thumb->image_path) }}" class="product-img" alt="{{ $product->name }}">
                @else
                <img src="https://via.placeholder.com/300x200?text=No+Image" class="product-img" alt="No image">
                @endif
            </div>
            <div class="card-body d-flex flex-column">
                <h6 class="card-title fw-bold mb-1">{{ $product->name }}</h6>

                @if($product->final_price < $product->price)
                    <p class="mb-1">
                        <span class="text-danger fw-bold">Rp{{ number_format($product->final_price, 0, ',', '.') }}</span>
                        <small class="text-muted text-decoration-line-through">Rp{{ number_format($product->price, 0, ',', '.') }}</small>
                    </p>
                    @else
                    <p class="mb-1 fw-bold">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
                    @endif

                    <p class="text-muted small mb-1">Stok: {{ $product->stock }}</p>
                    <p class="text-muted small flex-grow-1">{{ Str::limit($product->description, 60) }}</p>

                    <div class="mt-auto d-flex flex-column gap-2">
                        <a href="{{ route('buyer.products.show', $product->id) }}" class="btn btn-outline-info w-100">
                            <i class="bi bi-info-circle"></i> Lihat Detail
                        </a>

                        @if($product->stock > 0)
                        <a href="{{ route('buyer.cart.add', $product->id) }}" class="btn btn-primary w-100">
                            <i class="bi bi-cart-plus"></i> Tambah ke Cart
                        </a>
                        @else
                        <button class="btn btn-secondary w-100" disabled>Stok Habis</button>
                        @endif
                    </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info text-center p-4 shadow-sm border-0">
            <i class="bi bi-emoji-frown"></i> Tidak ada produk ditemukan.
        </div>
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-center mt-4">
    {!! $products->links() !!}
</div>