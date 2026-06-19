<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PondController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest redirect to login
Route::get('/', function () {
    return redirect('/login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboard AJAX endpoints
    Route::get('/dashboard/latest/{pondCode}', [DashboardController::class, 'latestData'])->name('dashboard.latest');
    Route::get('/dashboard/charts/{pondCode}', [DashboardController::class, 'chartData'])->name('dashboard.charts');
    Route::get('/dashboard/notifications/{pondCode}', [DashboardController::class, 'notifications'])->name('dashboard.notifications');
    Route::get('/dashboard/history/{pondCode}', [DashboardController::class, 'history'])->name('dashboard.history');
    Route::post('/dashboard/pump-control', [DashboardController::class, 'pumpControl'])->name('dashboard.pump-control');
    Route::post('/dashboard/notifications/{id}/read', [DashboardController::class, 'markNotificationRead'])->name('dashboard.notification.read');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('ponds', PondController::class);
        Route::get('devices', [DeviceTokenController::class, 'index'])->name('devices.index');
        Route::post('devices', [DeviceTokenController::class, 'store'])->name('devices.store');
        Route::post('devices/{device}/regenerate', [DeviceTokenController::class, 'regenerate'])->name('devices.regenerate');
        Route::patch('devices/{device}/revoke', [DeviceTokenController::class, 'revoke'])->name('devices.revoke');
    });
});

require __DIR__.'/auth.php';
