@extends('adminlte::page')

@section('title', 'Preview Import Produk')

@section('content_header')
<h1>Preview Import Produk</h1>
@stop

@section('content')
@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('admin.products.import') }}" method="POST">
    @csrf

    {{-- Simpan path file sementara supaya import bisa menemukan file-nya --}}
    <input type="hidden" name="file_path" value="{{ $filePath }}">
    <input type="hidden" name="seller_id" value="{{ $sellerId }}">



    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Deskripsi</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Seller ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td>{{ $row['description'] ?? '-' }}</td>
                    <td>{{ $row['price'] ?? 0 }}</td>
                    <td>{{ $row['stock'] ?? 0 }}</td>
                    <td>{{ $row['status'] ?? 'Inactive' }}</td>
                    <td>{{ $row['seller_id'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-success">✅ Konfirmasi Import</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">❌ Batal</a>
    </div>
</form>
@stop