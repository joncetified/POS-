<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/qr/meja/{tableNumber}/menu', [CustomerMenuController::class, 'table'])->name('customer.table.menu');
Route::post('/qr/meja/{tableNumber}/orders', [CustomerMenuController::class, 'submitTableOrder'])->name('customer.table.orders');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/email/verify', [AuthController::class, 'showVerification'])->name('verification.notice');
    Route::post('/email/verify', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('/email/verification-code', [AuthController::class, 'resendVerification'])->name('verification.resend');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:transactions.manage,reports.view_store,reports.view_all,cashiers.monitor,dashboard.view,profits.view,store.performance.view')
        ->name('dashboard.index');

    Route::get('/', [PosController::class, 'index'])
        ->middleware('permission:transactions.create,transactions.manage')
        ->name('pos.index');

    Route::get('/qr/meja', [CustomerMenuController::class, 'qrIndex'])
        ->middleware('permission:transactions.create,transactions.manage')
        ->name('customer.qr.index');

    Route::get('/menu', [CustomerMenuController::class, 'index'])
        ->middleware('permission:menu.view')
        ->name('customer.menu');

    Route::get('/sales', [SaleController::class, 'index'])
        ->middleware('permission:transactions.manage,reports.view_store,reports.view_all,cashiers.monitor,dashboard.view,profits.view')
        ->name('sales.index');

    Route::get('/sales/print', [SaleController::class, 'print'])
        ->middleware('permission:transactions.manage,reports.view_store,reports.view_all,cashiers.monitor,dashboard.view,profits.view')
        ->name('sales.print');

    Route::get('/sales/pdf', [SaleController::class, 'pdf'])
        ->middleware('permission:transactions.manage,reports.view_store,reports.view_all,cashiers.monitor,dashboard.view,profits.view')
        ->name('sales.pdf');

    Route::get('/sales/excel', [SaleController::class, 'excel'])
        ->middleware('permission:transactions.manage,reports.view_store,reports.view_all,cashiers.monitor,dashboard.view,profits.view')
        ->name('sales.excel');

    Route::get('/orders/open', [SaleController::class, 'openOrders'])
        ->middleware('permission:transactions.create,transactions.manage,payments.process')
        ->name('orders.open');

    Route::post('/orders/open', [SaleController::class, 'park'])
        ->middleware('permission:transactions.create,transactions.manage,payments.process')
        ->name('orders.park');

    Route::delete('/orders/open/{sale}', [SaleController::class, 'destroyOpen'])
        ->middleware('permission:transactions.create,transactions.manage,payments.process')
        ->name('orders.destroy');

    Route::post('/sales', [SaleController::class, 'store'])
        ->middleware('permission:transactions.create,transactions.manage,payments.process')
        ->name('sales.store');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view_store,reports.view_all,dashboard.view,profits.view,store.performance.view')
        ->name('reports.index');

    Route::get('/reports/print', [ReportController::class, 'print'])
        ->middleware('permission:reports.view_store,reports.view_all,dashboard.view,profits.view,store.performance.view')
        ->name('reports.print');

    Route::get('/reports/pdf', [ReportController::class, 'pdf'])
        ->middleware('permission:reports.view_store,reports.view_all,dashboard.view,profits.view,store.performance.view')
        ->name('reports.pdf');

    Route::get('/reports/excel', [ReportController::class, 'excel'])
        ->middleware('permission:reports.view_store,reports.view_all,dashboard.view,profits.view,store.performance.view')
        ->name('reports.excel');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:products.manage,inventory.manage')
        ->name('categories.store');

    Route::resource('products', ProductController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('permission:products.manage,stock.manage,inventory.manage');
});
