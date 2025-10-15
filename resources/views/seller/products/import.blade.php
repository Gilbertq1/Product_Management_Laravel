@extends('adminlte::page')

@section('title', 'Import Produk')

@section('content_header')
<h1>Import Produk</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        {{-- Kirim ke route preview dulu --}}
        <form action="{{ route('seller.products.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                {{-- Download Template CSV --}}
                <a href="{{ asset('templates/template_produk.csv') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-download"></i> Download Template CSV
                </a>

                {{-- Download Template XLSX --}}
                <a href="{{ asset('templates/template_produk.xlsx') }}" class="btn btn-outline-success btn-sm ml-2">
                    <i class="fas fa-download"></i> Download Template XLSX
                </a>

                <small class="text-muted d-block mt-1">
                    Kolom wajib: name, description, price, stock, status
                </small>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label">Upload File Produk (CSV/XLSX)</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Lanjut ke Preview</button>
        </form>
    </div>
</div>
@stop
