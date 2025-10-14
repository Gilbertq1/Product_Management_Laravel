<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Panel - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"> {{-- ✅ Bootstrap Icons --}}
    @stack('styles')
</head>

<body class="bg-light">

    {{-- 🔹 Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('buyer.products.index') }}">
                <i class="bi bi-shop"></i> Buyer Panel
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    {{-- ✅ Saldo --}}
                    <li class="nav-item me-3">
                        <span class="nav-link text-warning fw-bold">
                            <i class="bi bi-wallet2"></i>
                            Rp {{ number_format(Auth::user()->balance, 0, ',', '.') }}
                        </span>
                    </li>

                    {{-- ✅ Menu --}}
                    <li class="nav-item">
                        <a href="{{ route('buyer.products.index') }}" class="nav-link {{ request()->routeIs('buyer.products.*') ? 'active' : '' }}">
                            <i class="bi bi-box"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('buyer.cart.index') }}" class="nav-link {{ request()->routeIs('buyer.cart.*') ? 'active' : '' }}">
                            <i class="bi bi-cart3"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('buyer.orders.index') }}" class="nav-link {{ request()->routeIs('buyer.orders.*') ? 'active' : '' }}">
                            <i class="bi bi-bag-check"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- 🔹 Content --}}
    <main class="container py-4">
        @yield('content')
    </main>

    {{-- Bootstrap & Sweetalert --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')

    {{-- ✅ Toast Notification --}}
    <script>
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: "{{ session('success') }}",
            timer: 2500,
            showConfirmButton: false
        });
        @endif

        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "{{ session('error') }}",
        });
        @endif

        @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: 'Info',
            text: "{{ session('info') }}",
        });
        @endif
    </script>

</body>
</html>
