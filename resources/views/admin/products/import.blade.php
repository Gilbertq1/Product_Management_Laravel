@extends('adminlte::page')

@section('title', 'Import Products')

@section('content_header')
<h1>Import Produk</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <p>Unduh template lalu isi. Setelah itu upload file (.xlsx / .csv) dan klik "Upload & Lihat Preview".</p>

        <div class="mb-3">
            <a href="{{ route('admin.products.import.template', ['format' => 'xlsx']) }}" class="btn btn-outline-primary">
                <i class="fas fa-download"></i> Download Template (.xlsx)
            </a>

            <a href="{{ route('admin.products.import.template', ['format' => 'csv']) }}" class="btn btn-outline-secondary">
                <i class="fas fa-download"></i> Download Template (.csv)
            </a>
        </div>

        <form action="{{ route('admin.products.import.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Pilih seller --}}
            <div class="form-group">
                <label for="seller_id">Assign ke Seller</label>
                <select name="seller_id" id="seller_id" class="form-control" required>
                    <option value="">-- Pilih Seller --</option>
                    @foreach ($sellers as $seller)
                    <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="file">Pilih file (.xlsx / .csv)</label>
                <input type="file" name="file" id="file" accept=".xlsx,.csv" class="form-control-file" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-eye"></i> Upload & Lihat Preview
            </button>

            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Batal</a>
        </form>

    </div>
</div>
@stop