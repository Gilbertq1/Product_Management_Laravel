@extends('adminlte::page')

@section('title', 'Import Users')

@section('content_header')
    <h1>Import Data Users</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <p>Gunakan template berikut untuk memastikan format file import sesuai.</p>

        <div class="mb-3">
            <a href="{{ route('admin.users.import.template', 'xlsx') }}" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Download Template (.xlsx)
            </a>
            <a href="{{ route('admin.users.import.template', 'csv') }}" class="btn btn-info">
                <i class="fas fa-file-csv"></i> Download Template (.csv)
            </a>
        </div>

        <hr>

        <form action="{{ route('admin.users.import.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="file">Pilih File Import (.xlsx atau .csv)</label>
                <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-eye"></i> Preview Data
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@stop
