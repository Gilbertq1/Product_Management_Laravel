@extends('adminlte::page')

@section('title', 'Edit Discount for ' . $product->name)

@section('content_header')
    <h1>Edit Discount for {{ $product->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.discounts.update', [$product, $discount]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="percent" {{ old('type', $discount->type) == 'percent' ? 'selected' : '' }}>Percent</option>
                        <option value="fixed" {{ old('type', $discount->type) == 'fixed' ? 'selected' : '' }}>Fixed</option>
                    </select>
                    @error('type') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="value">Value</label>
                    <input type="number" name="value" step="0.01" 
                           class="form-control @error('value') is-invalid @enderror" 
                           value="{{ old('value', $discount->value) }}" required>
                    @error('value') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="datetime-local" name="start_date" 
                           class="form-control @error('start_date') is-invalid @enderror" 
                           value="{{ old('start_date', $discount->start_date ? $discount->start_date->format('Y-m-d\TH:i') : '') }}">
                    @error('start_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="datetime-local" name="end_date" 
                           class="form-control @error('end_date') is-invalid @enderror" 
                           value="{{ old('end_date', $discount->end_date ? $discount->end_date->format('Y-m-d\TH:i') : '') }}">
                    @error('end_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="{{ route('admin.products.discounts.index', $product) }}" class="btn btn-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
@stop
