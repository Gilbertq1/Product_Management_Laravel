@extends('adminlte::page')

@section('title', 'Preview Import Produk')

@section('content_header')
    <h1>Preview Import Produk</h1>
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('seller.products.import') }}" method="POST">
        @csrf

        {{-- Simpan path file sementara agar bisa ditemukan kembali saat import --}}
        <input type="hidden" name="file_path" value="{{ $file_path }}">

        @php
            // Gabungkan semua baris valid + invalid agar mudah di-loop
            $rows = array_merge($validRows ?? [], $invalidRows ?? []);
        @endphp

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Nama Produk</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $hasError = isset($row['errors']) && count($row['errors']) > 0;
                        @endphp
                        <tr class="{{ $hasError ? 'table-danger' : '' }}">
                            <td>{{ $row['name'] ?? '-' }}</td>
                            <td>{{ $row['description'] ?? '-' }}</td>
                            <td>{{ $row['price'] ?? 0 }}</td>
                            <td>{{ $row['stock'] ?? 0 }}</td>
                            <td>{{ $row['status'] ?? 'Inactive' }}</td>
                            <td>
                                @if ($hasError)
                                    <ul class="mb-0 text-danger">
                                        @foreach ($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-success">Valid ✅</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success"
                {{ collect($rows)->contains(fn($r) => isset($r['errors']) && count($r['errors']) > 0) ? 'disabled' : '' }}>
                ✅ Konfirmasi Import
            </button>
            <a href="{{ route('seller.products.index') }}" class="btn btn-secondary">❌ Batal</a>
        </div>
    </form>
@stop
