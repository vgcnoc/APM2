<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Endpoint untuk menerima data booking dari aplikasi lain (Membutuhkan Token)
Route::middleware('auth:sanctum')->post('/customers/booking', [BookingController::class, 'store']);

// Webhook Callback Payment Gateway (Tripay, Midtrans, dll)
Route::post('/payment-gateway/callback', [\App\Http\Controllers\PaymentGatewayController::class, 'callback'])->name('api.payment-gateway.callback');

