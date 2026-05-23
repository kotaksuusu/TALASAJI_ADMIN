<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SettingsController;

Route::get('/', fn() => redirect()->route('admin.login'));

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/umkm', [StoreController::class, 'index'])->name('umkm.index');
        Route::patch('/umkm/{id}/status', [StoreController::class, 'updateStatus'])->name('umkm.status');
        Route::patch('/umkm/{id}/approve', [StoreController::class, 'approve'])->name('umkm.approve');
        Route::patch('/umkm/{id}/reject', [StoreController::class, 'reject'])->name('umkm.reject');
        Route::get('/umkm/{id}', [StoreController::class, 'show'])->name('umkm.show');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });
});
