<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StorageController;
use App\Http\Controllers\Api\OwnerCategoryController;
use App\Http\Controllers\Api\OwnerMenuController;

// =========================================================================
// PUBLIC ENDPOINTS
// =========================================================================

// Auth — rate limited
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:5,1');

// Static file serving
Route::get('/avatar/{path}', [AuthController::class, 'serveAvatar'])->where('path', '.*');
Route::get('/storage/{path}', [StorageController::class, 'serve'])->where('path', '.*');

// Read-only browsing
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/menus', [MenuController::class, 'index']);
Route::get('/menus/{id}', [MenuController::class, 'show']);

// =========================================================================
// AUTHENTICATED ENDPOINTS (SANCTUM)
// =========================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ── Profile ────────────────────────────────────────────────────────────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // ── Stores ─────────────────────────────────────────────────────────────
    Route::post('/stores', [StoreController::class, 'store']);
    Route::get('user/store', [StoreController::class, 'myStore']);
    Route::put('/stores/{id}', [StoreController::class, 'update'])->middleware('store.owner');
    Route::delete('/stores/{id}', [StoreController::class, 'destroy'])->middleware('store.owner');
    Route::post('/stores/{id}/clone', [StoreController::class, 'clone'])->middleware('store.owner');

    // ── Categories ─────────────────────────────────────────────────────────
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('store.owner');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware('store.owner');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('store.owner');

    // ── Menus ──────────────────────────────────────────────────────────────
    Route::post('/menus', [MenuController::class, 'store'])->middleware('store.owner');
    Route::put('/menus/{id}', [MenuController::class, 'update'])->middleware('store.owner');
    Route::delete('/menus/{id}', [MenuController::class, 'destroy'])->middleware('store.owner');

    // ── Owner Master Menu (pemilik only) ───────────────────────────────────
    Route::prefix('owner')->middleware('role:pemilik')->group(function () {
        Route::apiResource('categories', OwnerCategoryController::class);
        Route::apiResource('menus', OwnerMenuController::class);
    });

    // ── Tables ───────────────────────────────────────────────────────────
    Route::get('/tables', [TableController::class, 'index'])->middleware('role:penjual,pemilik');
    Route::post('/tables', [TableController::class, 'store'])->middleware('role:penjual,pemilik');
    Route::get('/tables/{id}', [TableController::class, 'show']);
    Route::put('/tables/{id}', [TableController::class, 'update'])->middleware(['role:penjual,pemilik', 'store.owner']);
    Route::delete('/tables/{id}', [TableController::class, 'destroy'])->middleware(['role:penjual,pemilik', 'store.owner']);

    // ── Orders ─────────────────────────────────────────────────────────────
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware(['role:penjual,pemilik,admin', 'store.owner']);
    Route::post('/orders/{id}/items', [OrderController::class, 'addItem']);
    Route::delete('/orders/{id}/items/{itemId}', [OrderController::class, 'removeItem']);
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // ── Payments ───────────────────────────────────────────────────────────
    Route::post('/payments/callback', [PaymentController::class, 'callback']);
    Route::post('/payments/{orderId}', [PaymentController::class, 'store']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::get('/payments/by-order/{orderId}', [PaymentController::class, 'byOrder']);
    Route::put('/payments/{id}/confirm', [PaymentController::class, 'confirm'])
        ->middleware(['role:penjual,pemilik,admin', 'store.owner']);
    Route::put('/payments/{id}/cancel', [PaymentController::class, 'cancel'])
        ->middleware(['role:penjual,pemilik,admin', 'store.owner']);
    Route::post('/payments/{orderId}/proof', [PaymentController::class, 'uploadProof']);

    // ── Reviews ────────────────────────────────────────────────────────────
    Route::apiResource('reviews', ReviewController::class)->only(['index', 'store', 'show', 'destroy']);

    // ── Notifications ──────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // ── Upload ─────────────────────────────────────────────────────────────
    Route::post('/upload', [UploadController::class, 'store']);
});
