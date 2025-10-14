<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Panel</title>
    @vite('resources/css/app.css')
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">

<div class="flex h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-gray-100 flex flex-col">
        <div class="px-6 py-4 text-2xl font-bold border-b border-gray-800">
            Seller Panel
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="{{ route('seller.dashboard') }}"
               class="flex items-center px-3 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-800' : '' }}">
                <i class="ph ph-gauge mr-2"></i> Dashboard
            </a>
            <a href="{{ route('seller.products.index') }}"
               class="flex items-center px-3 py-2 rounded hover:bg-gray-800 {{ request()->routeIs('seller.products.*') ? 'bg-gray-800' : '' }}">
                <i class="ph ph-package mr-2"></i> Produk Saya
            </a>
            <a href="#"
               class="flex items-center px-3 py-2 rounded hover:bg-gray-800">
                <i class="ph ph-shopping-cart mr-2"></i> Order
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="{{ route('logout') }}"
               class="block text-red-400 hover:text-red-300 flex items-center">
                <i class="ph ph-sign-out mr-2"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">

        <!-- Topbar -->
        <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-700">@yield('page-title', 'Dashboard')</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">👋 Hi, {{ Auth::user()->name }}</span>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>

</div>

</body>
</html>
