@extends('adminlte::page')

@section('title', 'Discounts - ' . $product->name)

@section('content_header')
    <h1>Discounts for {{ $product->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>List of Discounts</span>
            <a href="{{ route('admin.products.discounts.create', $product->id) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Discount
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($discounts->count())
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Created At</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($discounts as $discount)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ ucfirst($discount->type) }}</td>
                                <td>
                                    @if ($discount->type === 'percent')
                                        {{ $discount->value }}%
                                    @else
                                        Rp {{ number_format($discount->value, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td>{{ $discount->start_date ?? '-' }}</td>
                                <td>{{ $discount->end_date ?? '-' }}</td>
                                <td>{{ $discount->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.products.discounts.edit', [$product->id, $discount->id]) }}"
                                        class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.products.discounts.destroy', [$product->id, $discount->id]) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this discount?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No discounts found for this product.</p>
            @endif
        </div>
    </div>
@stop
