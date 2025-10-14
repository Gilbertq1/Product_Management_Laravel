@extends('adminlte::page')

@section('title', 'Add User')

@section('content_header')
<h1>Add User</h1>
@stop

@section('content')
<form action="{{ route('admin.users.store') }}" method="POST">
    @csrf
    <div class="form-group mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-group mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="form-group mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-group mb-3">
        <label>Role</label>
        <select name="role" class="form-control">
            <option value="admin">Admin</option>
            <option value="seller">Seller</option>
            <option value="user" selected>User</option>
        </select>
    </div>
    <div class="form-group mb-3">
        <label>Balance</label>
        <input type="number" step="0.01" name="balance" class="form-control" value="0">
    </div>
    <button class="btn btn-success">Save</button>
</form>
@stop
