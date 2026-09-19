<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');

Route::prefix('menu')->name('menu.')->group(function () {
    Route::get('/table/{token}', [CustomerMenuController::class, 'show'])->middleware('throttle:60,1')->name('show');
    Route::post('/orders', [CustomerOrderController::class, 'store'])->middleware('throttle:customer-order')->name('orders.store');
    Route::get('/status', [CustomerMenuController::class, 'status'])->name('status');
    Route::post('/payment', [CustomerPaymentController::class, 'store'])->middleware('throttle:5,1')->name('payment.store');
});

Route::middleware('jwt.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/admin/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::prefix('admin')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
        Route::get('categories/create', [CategoryController::class, 'create'])->middleware('permission:categories.create')->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware('permission:categories.view')->name('categories.show');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->middleware('permission:categories.update')->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');

        Route::get('products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
        Route::get('products/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
        Route::get('products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.update')->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:products.update')->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');
        Route::get('products/{product}/recipe', [RecipeController::class, 'edit'])->middleware('permission:inventory.update')->name('recipes.edit');
        Route::put('products/{product}/recipe', [RecipeController::class, 'update'])->middleware('permission:inventory.update')->name('recipes.update');
        Route::get('tables', [TableController::class, 'index'])->middleware('permission:tables.view')->name('tables.index');
        Route::get('tables/create', [TableController::class, 'create'])->middleware('permission:tables.create')->name('tables.create');
        Route::post('tables', [TableController::class, 'store'])->middleware('permission:tables.create')->name('tables.store');
        Route::get('tables/{table}', [TableController::class, 'show'])->middleware('permission:tables.view')->name('tables.show');
        Route::get('tables/{table}/edit', [TableController::class, 'edit'])->middleware('permission:tables.update')->name('tables.edit');
        Route::put('tables/{table}', [TableController::class, 'update'])->middleware('permission:tables.update')->name('tables.update');
        Route::delete('tables/{table}', [TableController::class, 'destroy'])->middleware('permission:tables.delete')->name('tables.destroy');
        Route::get('tables/{table}/qr', [TableController::class, 'qr'])->middleware('permission:tables.qr')->name('tables.qr');
        Route::get('tables/{table}/qr/download', [TableController::class, 'downloadQr'])->middleware('permission:tables.qr')->name('tables.qr.download');
        Route::post('tables/{table}/qr/regenerate', [TableController::class, 'regenerateQr'])->middleware('permission:tables.qr')->name('tables.qr.regenerate');

        Route::get('orders', [OrderManagementController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
        Route::get('orders/{order}', [OrderManagementController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
        Route::patch('orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->middleware('permission:orders.update')->name('orders.status');

        Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('payments.store');
        Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->middleware('permission:payments.confirm')->name('payments.confirm');
        Route::patch('payments/{payment}/fail', [PaymentController::class, 'fail'])->middleware('permission:payments.confirm')->name('payments.fail');
        Route::get('inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
        Route::post('inventory/items', [InventoryController::class, 'storeItem'])->middleware('permission:inventory.create')->name('inventory.items.store');
        Route::put('inventory/items/{inventoryItem}', [InventoryController::class, 'updateItem'])->middleware('permission:inventory.update')->name('inventory.items.update');
        Route::delete('inventory/items/{inventoryItem}', [InventoryController::class, 'destroyItem'])->middleware('permission:inventory.update')->name('inventory.items.destroy');
        Route::get('inventory/transactions', [InventoryController::class, 'transactions'])->middleware('permission:inventory.view')->name('inventory.transactions');
        Route::post('inventory/transactions', [InventoryController::class, 'storeTransaction'])->middleware('permission:inventory.adjust')->name('inventory.transactions.store');

        Route::resource('suppliers', SupplierController::class)
            ->middlewareFor(['index', 'show'], 'permission:suppliers.view')
            ->middlewareFor(['create', 'store'], 'permission:suppliers.create')
            ->middlewareFor(['edit', 'update'], 'permission:suppliers.update')
            ->middlewareFor('destroy', 'permission:suppliers.delete');
        Route::resource('users', UserController::class)
            ->middlewareFor(['index', 'show'], 'permission:users.view')
            ->middlewareFor(['create', 'store'], 'permission:users.create')
            ->middlewareFor(['edit', 'update'], 'permission:users.update')
            ->middlewareFor('destroy', 'permission:users.delete');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.update')->name('users.reset-password');
        Route::resource('roles', RoleController::class)
            ->middlewareFor(['index', 'show'], 'permission:roles.view')
            ->middlewareFor(['create', 'store'], 'permission:roles.create')
            ->middlewareFor(['edit', 'update'], 'permission:roles.update')
            ->middlewareFor('destroy', 'permission:roles.delete');
        Route::get('reports', ReportController::class)->middleware('permission:reports.view')->name('reports.index');
        Route::get('activity-logs', ActivityLogController::class)->middleware('permission:activity_logs.view')->name('activity-logs.index');
    });
    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/', [KitchenController::class, 'index'])->middleware('permission:orders.view')->name('index');
        Route::patch('/orders/{order}', [KitchenController::class, 'update'])->middleware('permission:orders.update')->name('orders.update');
    });
});
