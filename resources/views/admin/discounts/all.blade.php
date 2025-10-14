@extends('adminlte::page')

@section('title', 'All Discounts')

@section('content_header')
    <h1>All Discounts</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table id="discountsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $d)
                        <tr>
                            <td>{{ $d->product->name }}</td>
                            <td>{{ ucfirst($d->type) }}</td>
                            <td>
                                @if($d->type == 'percent')
                                    {{ $d->value }}%
                                @else
                                    Rp {{ number_format($d->value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td>{{ $d->start_date ? $d->start_date->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $d->end_date ? $d->end_date->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $d->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.products.discounts.edit', [$d->product, $d]) }}" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.products.discounts.destroy', [$d->product, $d]) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this discount?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No discounts available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
<script>
    $(function () {
        $('#discountsTable').DataTable();
    });
</script>
@stop
