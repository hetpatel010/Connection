<?php

use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SalesInvoiceController;
use App\Http\Middleware\EnsureApiBusinessHeader;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'data' => ['status' => 'ok'],
        'meta' => [],
        'errors' => [],
    ]));

    Route::middleware(EnsureApiBusinessHeader::class)->group(function (): void {
        Route::get('/customers', [PartyController::class, 'index']);
        Route::get('/customers/{party}', [PartyController::class, 'show']);

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        Route::get('/sales-invoices', [SalesInvoiceController::class, 'index']);
        Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show']);
    });
});
