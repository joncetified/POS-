<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerMenuController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/qr/meja/{tableNumber}/menu', [CustomerMenuController::class, 'table'])->name('customer.table.menu');
Route::post('/qr/meja/{tableNumber}/orders', [CustomerMenuController::class, 'submitTableOrder'])->name('customer.table.orders');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::post('/login/fingerprint/options', [AuthController::class, 'fingerprintOptions'])->name('login.fingerprint.options');
    Route::post('/login/fingerprint', [AuthController::class, 'fingerprintLogin'])->name('login.fingerprint');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/email/verify', [AuthController::class, 'showVerification'])->name('verification.notice');
    Route::post('/email/verify', [AuthController::class, 'verify'])->name('verification.verify');
    Route::post('/email/verification-code', [AuthController::class, 'resendVerification'])->name('verification.resend');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/fingerprint/options', [ProfileController::class, 'fingerprintOptions'])->name('profile.fingerprint.options');
    Route::put('/profile/fingerprint', [ProfileController::class, 'updateFingerprint'])->name('profile.fingerprint.update');

    Route::get('/access-control', [AccessControlController::class, 'index'])->name('access-control.index');
    Route::match(['put', 'patch'], '/access-control/{user}', [AccessControlController::class, 'update'])->name('access-control.update');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:page.settings')
        ->name('settings.index');

    Route::put('/settings', [SettingsController::class, 'update'])
        ->middleware('permission:page.settings')
        ->name('settings.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:page.dashboard')
        ->name('dashboard.index');

    Route::get('/', [PosController::class, 'index'])
        ->middleware('permission:page.pos')
        ->name('pos.index');

    Route::get('/qr/meja', [CustomerMenuController::class, 'qrIndex'])
        ->middleware('permission:page.qr_tables')
        ->name('customer.qr.index');

    Route::get('/menu', [CustomerMenuController::class, 'index'])
        ->middleware('permission:page.customer_menu')
        ->name('customer.menu');

    Route::get('/sales', [SaleController::class, 'index'])
        ->middleware('permission:page.sales')
        ->name('sales.index');

    Route::get('/sales/print', [SaleController::class, 'print'])
        ->middleware('permission:page.sales_export')
        ->name('sales.print');

    Route::get('/sales/pdf', [SaleController::class, 'pdf'])
        ->middleware('permission:page.sales_export')
        ->name('sales.pdf');

    Route::get('/sales/excel', [SaleController::class, 'excel'])
        ->middleware('permission:page.sales_export')
        ->name('sales.excel');

    Route::get('/orders/open', [SaleController::class, 'openOrders'])
        ->middleware('permission:page.orders')
        ->name('orders.open');

    Route::post('/orders/open', [SaleController::class, 'park'])
        ->middleware('permission:page.orders')
        ->name('orders.park');

    Route::delete('/orders/open/{sale}', [SaleController::class, 'destroyOpen'])
        ->middleware('permission:page.orders')
        ->name('orders.destroy');

    Route::post('/sales', [SaleController::class, 'store'])
        ->middleware('permission:page.pos,page.orders')
        ->name('sales.store');

    Route::post('/payments/qris', [SaleController::class, 'qrisCharge'])
        ->middleware('permission:page.pos,page.orders')
        ->name('payments.qris.charge');

    Route::post('/payments/qris/finalize', [SaleController::class, 'qrisFinalize'])
        ->middleware('permission:page.pos,page.orders')
        ->name('payments.qris.finalize');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:page.reports')
        ->name('reports.index');

    Route::get('/reports/print', [ReportController::class, 'print'])
        ->middleware('permission:page.reports_export')
        ->name('reports.print');

    Route::get('/reports/pdf', [ReportController::class, 'pdf'])
        ->middleware('permission:page.reports_export')
        ->name('reports.pdf');

    Route::get('/reports/excel', [ReportController::class, 'excel'])
        ->middleware('permission:page.reports_export')
        ->name('reports.excel');

    Route::get('/operations', [OperationController::class, 'index'])
        ->middleware('permission:page.operations')
        ->name('operations.index');

    Route::post('/operations/employees', [OperationController::class, 'storeEmployee'])
        ->middleware('permission:page.operations')
        ->name('operations.employees.store');

    Route::post('/operations/salaries', [OperationController::class, 'storeSalary'])
        ->middleware('permission:page.operations')
        ->name('operations.salaries.store');

    Route::post('/operations/expenses', [OperationController::class, 'storeExpense'])
        ->middleware('permission:page.operations')
        ->name('operations.expenses.store');

    Route::post('/operations/inventory-movements', [OperationController::class, 'storeInventoryMovement'])
        ->middleware('permission:page.operations')
        ->name('operations.inventory.store');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:page.products')
        ->name('categories.store');

    Route::resource('products', ProductController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('permission:page.products');
});
