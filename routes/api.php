<?php

use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\RefundController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', [CustomerApiController::class, 'health']);
Route::get('/customers', [CustomerApiController::class, 'index']);
Route::get('/customers/{id}/orders', [CustomerApiController::class, 'orders']);

// Refund evaluation endpoints
Route::post('/refunds', [RefundController::class, 'store']);
Route::post('/refunds/evaluate', [RefundController::class, 'evaluate']);

// Refund requests audit, details, and supervisor overrides
Route::get('/refund-requests', [RefundController::class, 'index']);
Route::get('/refund-requests/{id}', [RefundController::class, 'show']);
Route::post('/refund-requests/{id}/override', [RefundController::class, 'override']);

// Backward compatibility & admin aliases
Route::get('/admin/refunds', [RefundController::class, 'index']);
Route::post('/admin/refunds/{id}/override', [RefundController::class, 'override']);
