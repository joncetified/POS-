<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/', [PosController::class, 'index'])->name('pos.index');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
});
