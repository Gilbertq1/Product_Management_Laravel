@extends('adminlte::page')

@section('title', 'Create Product')

@section('content_header')
    <h1>Create Product</h1>
@stop

@section('content')
<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    @if(auth()->user()->role === 'admin')
    <div class="form-group mb-3">
        <label>Seller</label>
        <select name="seller_id" class="form-control">
            <option value="">-- None --</option>
            @foreach($sellers as $seller)
                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="form-group mb-3">
        <label>Product Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="form-group mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <div class="form-group mb-3">
        <label>Price (Rp)</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
    </div>

    <div class="form-group mb-3">
        <label>Stock</label>
        <input type="number" name="stock" class="form-control" required>
    </div>

    {{-- Categories --}}
    <div class="form-group mb-3">
        <label class="fw-bold mb-2">Categories</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach($categories as $category)
            <label class="category-card shadow-sm rounded p-3 text-center">
                <input type="checkbox"
                       name="categories[]"
                       value="{{ $category->id }}"
                       class="category-checkbox">
                <div class="category-icon mb-2">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="category-name fw-semibold">{{ $category->name }}</div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Thumbnail --}}
    <div class="form-group mb-3">
        <label>Thumbnail</label>
        <input type="file" name="thumbnail" class="form-control" accept="image/*" id="thumbnailInput">
        <div id="thumbPreview" class="mt-2"></div>
    </div>

    {{-- Images --}}
    <div class="form-group mb-3">
        <label>Product Images</label>
        <input type="file" name="images[]" class="form-control" multiple accept="image/*" id="imagesInput">
        <small class="text-muted">Bisa pilih lebih dari satu.</small>
        <div id="preview" class="mt-3 d-flex flex-wrap gap-3"></div>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="status" id="status" value="1" class="form-check-input">
        <label class="form-check-label" for="status">Active</label>
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@stop

@section('css')
<style>
    .category-card {
        cursor: pointer;
        width: 140px;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px solid #e0e0e0;
        transition: all .2s ease;
        user-select: none;
        position: relative;
    }

    .category-card input[type="checkbox"] {
        position: absolute;
        top: 8px;
        right: 8px;
        transform: scale(1.2);
        cursor: pointer;
    }

    .category-card:hover {
        border-color: #0d6efd;
        background-color: #f0f8ff;
        transform: translateY(-3px);
    }

    .category-card input[type="checkbox"]:checked ~ .category-icon,
    .category-card input[type="checkbox"]:checked ~ .category-name {
        color: #0d6efd;
        font-weight: 600;
    }

    .category-card input[type="checkbox"]:checked ~ .category-name {
        border-top: 2px solid #0d6efd;
        padding-top: 5px;
    }

    .thumb-preview,
    .img-preview {
        border: 2px solid #ddd;
        border-radius: 6px;
        object-fit: cover;
    }

    .thumb-preview {
        width: 150px;
        height: 150px;
    }

    .img-preview {
        width: 120px;
        height: 120px;
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview thumbnail
    const thumbInput = document.getElementById('thumbnailInput');
    const thumbPreview = document.getElementById('thumbPreview');
    thumbInput?.addEventListener('change', function() {
        thumbPreview.innerHTML = '';
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('thumb-preview');
                thumbPreview.appendChild(img);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Preview multiple images
    const imagesInput = document.getElementById('imagesInput');
    const preview = document.getElementById('preview');
    imagesInput?.addEventListener('change', function() {
        preview.innerHTML = '';
        [...this.files].forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-preview');
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
});
</script>
@stop