<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;

Route::get('/', [\App\Http\Controllers\FacilityController::class, 'publicIndex'])->name('public.facilities');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('facilities', App\Http\Controllers\FacilityController::class)->except(['show']);
    Route::get('/facility-reports/export', [\App\Http\Controllers\FacilitySummaryController::class, 'exportCsv'])->name('facility-reports.export');
    Route::get('/facility-reports', [\App\Http\Controllers\FacilitySummaryController::class, 'index'])->name('facility-reports.index');
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    
    Route::get('/reservations/export', [ReservationController::class, 'exportCsv'])->name('reservations.export');
    Route::get('/reservations/create/{facility}', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.updateStatus');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('/reservations/{reservation}/admin-cancel', [ReservationController::class, 'adminCancel'])->name('reservations.adminCancel');

    Route::resource('reports', App\Http\Controllers\ReportController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/reports/{report}/status', [App\Http\Controllers\ReportController::class, 'updateStatus'])->name('reports.updateStatus');
    
    Route::resource('users', App\Http\Controllers\UserController::class)->only(['index', 'store']);
    Route::post('/users/{user}/status', [App\Http\Controllers\UserController::class, 'updateStatus'])->name('users.updateStatus');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
