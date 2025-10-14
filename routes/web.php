<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;

// Admin
use App\Http\Controllers\Admin\{
    UserController,
    ProductController,
    OrderController,
    OrderItemController,
    CategoryController,
    ActivityLogController,
    DashboardController
};
use App\Http\Controllers\DiscountController;

// Seller
use App\Http\Controllers\Seller\{
    SellerProductController,
    SellerDashboardController
};

// Buyer
use App\Http\Controllers\Buyer\{
    BuyerProductController,
    BuyerCartController,
    BuyerOrderController,
    CheckoutController
};

// ================== PUBLIC ROUTES ==================
Route::view('/', 'welcome');
Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

// ================== PROFILE ==================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// ================== ADMIN ROUTES ==================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // === Users ===
    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('import', 'importForm')->name('users.import.form'); // <- form upload + download template
        Route::get('import/template/{format?}', 'downloadTemplate')->name('users.import.template'); // <- download .xlsx / .csv
        Route::match(['get', 'post'], 'import/preview', 'preview')->name('users.import.preview'); // <- preview hasil import
        Route::post('import', 'import')->name('users.import'); // <- proses import final

        Route::post('bulk-action', 'bulkAction')->name('users.bulkAction');
        Route::post('{id}/restore', 'restore')->name('users.restore');
        Route::delete('{id}/force-delete', 'forceDelete')->name('users.forceDelete');
        Route::get('export', 'export')->name('users.export');
    });

    Route::resource('users', UserController::class);
    // === Products ===
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('import', 'importForm')->name('products.import.form');                 // <- new: form upload + download template
        Route::get('import/template/{format?}', 'downloadTemplate')->name('products.import.template'); // <- new: download .xlsx/.csv
        Route::post('bulk-action', 'bulkAction')->name('products.bulkAction');
        Route::post('{id}/restore', 'restore')->name('products.restore');
        Route::delete('{id}/force-delete', 'forceDelete')->name('products.forceDelete');
        Route::post('import/preview', 'preview')->name('products.import.preview');
        Route::get('export', 'export')->name('products.export');
        Route::post('import', 'import')->name('products.import');
    });

    Route::resource('products', ProductController::class);
    Route::resource('products.discounts', DiscountController::class)->shallow();
    Route::get('discounts', [DiscountController::class, 'indexAll'])->name('discounts.indexAll');

    // === Categories ===
    Route::controller(CategoryController::class)->group(function () {
        Route::post('categories/bulk-action', 'bulkAction')->name('categories.bulkAction');
        Route::post('categories/{id}/restore', 'restore')->name('categories.restore');
        Route::delete('categories/{id}/force-delete', 'forceDelete')->name('categories.forceDelete');
    });
    Route::resource('categories', CategoryController::class);

    // === Orders ===
    Route::controller(OrderController::class)->group(function () {
        Route::post('orders/bulk-action', 'bulkAction')->name('orders.bulkAction');
        Route::put('orders/{order}/pay', 'pay')->name('orders.pay');
        Route::post('orders/{id}/restore', 'restore')->name('orders.restore');
        Route::delete('orders/{id}/force-delete', 'forceDelete')->name('orders.forceDelete');
    });
    Route::resource('orders', OrderController::class);

    // === Order Items ===
    Route::resource('orders.items', OrderItemController::class)->shallow();

    // === Activity Logs ===
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity_logs.index');
});

// ================== SELLER ROUTES ==================
Route::prefix('seller')->name('seller.')->middleware(['auth', 'role:seller'])->group(function () {
    Route::get('/', [SellerDashboardController::class, 'index'])->name('dashboard');

    Route::controller(SellerProductController::class)->group(function () {
        Route::get('products/import', 'importForm')->name('products.importForm');
        Route::post('products/import', 'import')->name('products.import');
        Route::get('products/export', 'export')->name('products.export');
        Route::post('products/{id}/restore', 'restore')->name('products.restore');
        Route::delete('products/{id}/force-delete', 'forceDelete')->name('products.forceDelete');
        Route::post('products/bulk-action', 'bulkAction')->name('products.bulkAction');
    });
    Route::resource('products', SellerProductController::class);
});

// ================== BUYER ROUTES ==================
Route::prefix('buyer')->name('buyer.')->middleware(['auth', 'role:user'])->group(function () {

    // === Products ===
    Route::get('products/live-search', [BuyerProductController::class, 'liveSearch'])->name('products.liveSearch');
    Route::resource('products', BuyerProductController::class)->only(['index', 'show']);

    // === Cart ===
    Route::controller(BuyerCartController::class)->prefix('cart')->name('cart.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('add/{id}', 'addToCart')->name('add');
        Route::put('update/{id}', 'updateCart')->name('update');
        Route::delete('remove/{id}', 'removeFromCart')->name('remove');
        Route::post('checkout', 'checkout')->name('checkout');
    });

    // === Orders ===
    Route::controller(BuyerOrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{order}', 'show')->name('show');
        Route::patch('{order}/cancel', [BuyerCartController::class, 'cancel'])->name('cancel');
    });

    // === Checkout ===
    Route::post('checkout/pay/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');
    Route::post('orders/{order}/pay', [CheckoutController::class, 'pay'])->name('orders.pay');
});
