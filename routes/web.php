<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ShopFloorController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', HomeController::class)->name('home');

    Route::get('dashboard', DashboardController::class)->middleware('can:view-dashboard')->name('dashboard');
    Route::get('shop-floor', [ShopFloorController::class, 'index'])->middleware('can:use-shop-floor')->name('shop-floor.index');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
});
