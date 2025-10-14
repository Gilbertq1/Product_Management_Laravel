@extends('adminlte::page')

@section('title', 'Edit Produk')

@section('content_header')
<h1>Edit Produk</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('seller.products.update', $product) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <select name="categories[]" id="categories" class="form-control select2" multiple="multiple" required>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ in_array($cat->id, old('categories', $selectedCategories)) ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
                <small class="text-muted">Pilih satu atau lebih kategori</small>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label>Harga</label>
                    <input type="number" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                </div>
                <div class="col-md-4">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock) }}" required>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="status" name="status" value="1"
                            {{ old('status', $product->status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Aktif</label>
                    </div>
                </div>
            </div>

            {{-- Thumbnail --}}
            <div class="form-group mt-3">
                <label>Thumbnail Produk <span class="text-danger">*</span></label><br>
                @if($product->thumbnail_url)
                <img src="{{ asset($product->thumbnail_url) }}"
                    alt="Thumbnail" width="120" class="mb-2 rounded border">
                @endif
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                <small class="text-muted">Pilih gambar utama baru jika ingin mengganti thumbnail</small>
            </div>

            {{-- Gambar tambahan --}}
            <div class="form-group mt-3">
                <label>Gambar Tambahan</label><br>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($product->images->where('is_thumbnail', false) as $img)
                    <img src="{{ asset('storage/'.$img->image_path) }}"
                        alt="Image" width="100" class="rounded border">
                    @endforeach
                </div>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">Pilih gambar tambahan baru jika ingin menambahkan</small>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('seller.products.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Badge kategori terpilih */
    .select2-container--default .select2-selection__choice {
        background-color: #007bff !important;
        border: none !important;
        color: #fff !important;
        margin-top: 4px !important;
        border-radius: 16px !important;
        font-size: 0.85rem !important;
        display: inline-flex !important;
        align-items: center !important;
        padding: 4px 12px 4px 20px !important;
        position: relative;
    }

    /* Tombol silang */
    .select2-container--default .select2-selection__choice__remove {
        position: absolute !important;
        left: -3px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #fff !important;
        font-size: 14px !important;
        cursor: pointer;
        background: gray!important;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .select2-container--default .select2-selection__choice__remove:hover {
        background: black !important;
        color: #fff !important;
    }

    /* Hover di dropdown */
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0056b3 !important;
        color: #fff !important;
    }

    /* Placeholder */
    .select2-container--default .select2-selection__placeholder {
        color: #6c757d !important;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#categories').select2({
            placeholder: "Pilih kategori",
            allowClear: true,
            width: '100%'
        });
    });
</script>
@stop
