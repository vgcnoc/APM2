<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);

    // Pendaftaran Mandiri Mitra Reseller & Biller (Public)
    Route::get('/register-reseller', [\App\Http\Controllers\Auth\RegisterResellerController::class, 'create'])->name('register-reseller');
    Route::post('/register-reseller', [\App\Http\Controllers\Auth\RegisterResellerController::class, 'store'])->name('register-reseller.store');

    // Pendaftaran Mandiri Mitra Marketing & Afiliasi (Public)
    Route::get('/register-affiliate', [\App\Http\Controllers\Auth\RegisterAffiliateController::class, 'create'])->name('register-affiliate');
    Route::post('/register-affiliate', [\App\Http\Controllers\Auth\RegisterAffiliateController::class, 'store'])->name('register-affiliate.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
