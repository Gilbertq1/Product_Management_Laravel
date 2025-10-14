@extends('adminlte::page')

@section('title', 'Seller Dashboard')

@section('content_header')
    <h1>Seller Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <!-- Saldo -->
        <div class="col-lg-4 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($user->balance, 0, ',', '.') }}</h3>
                    <p>Saldo</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>

        <!-- Total Produk -->
        <div class="col-lg-4 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalProducts }}</h3>
                    <p>Total Produk</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>

        <!-- Produk Terjual -->
        <div class="col-lg-4 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalSold }}</h3>
                    <p>Produk Terjual</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Produk Terlaris -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Produk Terlaris</h3>
        </div>
        <div class="card-body">
            @if($bestSeller && $bestSeller->product)
                <div class="d-flex align-items-center">
                    <img src="{{ asset('storage/' . $bestSeller->product->image_url) }}" 
                         class="img-thumbnail mr-3" style="width:80px; height:80px; object-fit:cover;">
                    <div>
                        <h5 class="mb-1">{{ $bestSeller->product->name }}</h5>
                        <small class="text-muted">Terjual {{ $bestSeller->total }} kali</small>
                    </div>
                </div>
            @else
                <p class="text-muted">Belum ada produk terjual</p>
            @endif
        </div>
    </div>
@stop
