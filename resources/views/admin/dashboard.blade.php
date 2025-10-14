@extends('adminlte::page')

@section('title', 'Admin Dashboard')

@section('content_header')
    <h1>Dashboard Admin</h1>
@stop

@section('content')
<div class="row">
    <!-- Info Boxes -->
    <div class="col-lg-2 col-6">
        <x-adminlte-small-box title="{{ $userCount }}" text="Users" icon="fas fa-users" theme="info"/>
    </div>
    <div class="col-lg-2 col-6">
        <x-adminlte-small-box title="{{ $productCount }}" text="Products" icon="fas fa-box" theme="success"/>
    </div>
    <div class="col-lg-2 col-6">
        <x-adminlte-small-box title="{{ $orderCount }}" text="Orders" icon="fas fa-shopping-cart" theme="warning"/>
    </div>
    <div class="col-lg-3 col-6">
        <x-adminlte-small-box title="Rp {{ number_format($totalRevenue, 0, ',', '.') }}" text="Total Revenue" icon="fas fa-coins" theme="primary"/>
    </div>
    <div class="col-lg-3 col-6">
        <x-adminlte-small-box title="Rp {{ number_format($averageOrderValue, 0, ',', '.') }}" text="Average Order Value" icon="fas fa-chart-line" theme="secondary"/>
    </div>
</div>

@if($lowStockProducts->count())
    <div class="alert alert-danger">
        <strong>⚠ Warning!</strong> Produk hampir habis:
        <ul class="mt-2 mb-0">
            @foreach($lowStockProducts as $p)
                <li>{{ $p->name }} (stok: {{ $p->stock }})</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <!-- Orders Chart -->
    <div class="col-md-6">
        <x-adminlte-card title="Orders per Month" theme="lightblue" body-class="p-0" body-style="height:300px;">
            <canvas id="ordersChart"></canvas>
        </x-adminlte-card>
    </div>

    <!-- Revenue Chart -->
    <div class="col-md-6">
        <x-adminlte-card title="Revenue per Month" theme="teal" body-class="p-0" body-style="height:300px;">
            <canvas id="revenueChart"></canvas>
        </x-adminlte-card>
    </div>
</div>

<div class="row">
    <!-- Users by Role -->
    <div class="col-md-6">
        <x-adminlte-card title="Users by Role" theme="indigo" body-class="p-0" body-style="height:300px;">
            <canvas id="usersChart"></canvas>
        </x-adminlte-card>
    </div>

    <!-- Top Products -->
    <div class="col-md-6">
        <x-adminlte-card title="Top Products" theme="warning" body-class="p-0">
            <table class="table table-sm">
                <thead><tr><th>Product</th><th>Sold</th></tr></thead>
                <tbody>
                    @forelse($topProducts as $tp)
                        <tr><td>{{ $tp->name }}</td><td>{{ $tp->total_sold }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-adminlte-card>
    </div>
</div>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-md-6">
        <x-adminlte-card title="Recent Orders" theme="info" body-class="p-0">
            <table class="table table-sm">
                <thead><tr><th>ID</th><th>User</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->user?->name ?? '-' }}</td>
                            <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No orders yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-adminlte-card>
    </div>

    <!-- Top Customers -->
    <div class="col-md-6">
        <x-adminlte-card title="Top Customers" theme="success" body-class="p-0">
            <table class="table table-sm">
                <thead><tr><th>User</th><th>Orders</th><th>Spent</th></tr></thead>
                <tbody>
                    @forelse($topCustomers as $tc)
                        <tr>
                            <td>{{ $tc->name }}</td>
                            <td>{{ $tc->total_orders }}</td>
                            <td>Rp {{ number_format($tc->total_spent, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-adminlte-card>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Orders chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: @json($ordersPerMonth['labels']),
            datasets: [{
                label: 'Orders',
                data: @json($ordersPerMonth['data']),
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Revenue chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: @json($revenuePerMonth['labels']),
            datasets: [{
                label: 'Revenue',
                data: @json($revenuePerMonth['data']),
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Users chart
    new Chart(document.getElementById('usersChart'), {
        type: 'doughnut',
        data: {
            labels: @json(array_keys($usersByRole)),
            datasets: [{
                data: @json(array_values($usersByRole)),
                backgroundColor: ['#007bff','#28a745','#ffc107']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins:{ legend:{ position:'bottom' } } }
    });
</script>
@stop
