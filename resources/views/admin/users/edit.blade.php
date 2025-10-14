@extends('adminlte::page')

@section('title', 'Edit User')

@section('content_header')
<h1>Edit User</h1>
@stop

@section('content')
<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group mb-3">
        <label>Name</label>
        <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
    </div>
    <div class="form-group mb-3">
        <label>Email</label>
        <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
    </div>
    <div class="form-group mb-3">
        <label>Password (leave blank if not changing)</label>
        <input type="password" name="password" class="form-control">
    </div>
    <div class="form-group mb-3">
        <label>Role</label>
        <select name="role" class="form-control">
            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="seller" {{ $user->role == 'seller' ? 'selected' : '' }}>Seller</option>
            <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>User</option>
        </select>
    </div>
    <div class="form-group mb-3">
        <label>Balance</label>
        <input type="number" step="0.01" name="balance" class="form-control" value="{{ $user->balance }}">
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@stop
