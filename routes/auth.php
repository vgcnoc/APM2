<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);

    // Pendaftaran Mandiri Mitra Reseller & Biller (Public)
    Route::get('/register-reseller', [\App\Http\Controllers\Auth\RegisterResellerController::class, 'create'])->name('register-reseller');
    Route::post('/register-reseller', [\App\Http\Controllers\Auth\RegisterResellerController::class, 'store'])->name('register-reseller.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
