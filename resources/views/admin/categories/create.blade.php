@extends('adminlte::page')

@section('title', 'Tambah Category')

@section('content_header')
    <h1>Tambah Category</h1>
@stop

@section('content')
    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Nama Category</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label>Slug (opsional)</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
                    <small class="text-muted">Biarkan kosong untuk generate otomatis dari nama.</small>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </form>
@stop
