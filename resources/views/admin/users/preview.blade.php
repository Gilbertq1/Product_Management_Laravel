@extends('adminlte::page')

@section('title', 'Preview Import Data User')

@section('content_header')
<h1>Preview Import Data User</h1>
@stop

@section('content')
<div class="container">
    <form action="{{ route('admin.users.import') }}" method="POST">
        @csrf
        <input type="hidden" name="file_path" value="{{ $filePath }}">

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Role</th>
                    <th>balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td>{{ $row['email'] ?? '-' }}</td>
                    <td>{{ $row['password'] ?? '-' }}</td>
                    <td>{{ $row['role'] ?? '-' }}</td>
                    <td>{{ $row['balance'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Konfirmasi Import
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>
@stop