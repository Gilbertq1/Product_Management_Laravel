@extends('adminlte::page')

@section('title', 'Edit Category - ' . $category->name)

@section('content_header')
    <h1>Edit Category - {{ $category->name }}</h1>
@stop

@section('content')
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Nama Category</label>
                    <input type="text" name="name" class="form-control" 
                           value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Slug (opsional)</label>
                    <input type="text" name="slug" class="form-control" 
                           value="{{ old('slug', $category->slug) }}">
                    <small class="text-muted">Biarkan kosong untuk generate otomatis dari nama.</small>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary">Update</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </form>
@stop
