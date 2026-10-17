<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\MaterialController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProductionOrderController;
use App\Http\Controllers\Web\ProductionStepController;
use App\Http\Controllers\Web\SalesOrderController;
use App\Http\Controllers\Web\SalesOrderPdfController;
use App\Http\Controllers\Web\SalesOrderWorkflowController;
use App\Http\Controllers\Web\ShopFloorController;
use App\Http\Controllers\Web\StockMovementController;
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

    Route::resource('customers', CustomerController::class);

    Route::resource('sales-orders', SalesOrderController::class)->except('destroy');
    Route::controller(SalesOrderWorkflowController::class)->prefix('sales-orders/{sales_order}')->name('sales-orders.')->group(function (): void {
        Route::post('confirm', 'confirm')->name('confirm');
        Route::post('cancel', 'cancel')->name('cancel');
        Route::post('ship', 'ship')->name('ship');
    });
    Route::post('sales-orders/{sales_order}/pdf', [SalesOrderPdfController::class, 'store'])->name('sales-orders.pdf.store');
    Route::get('sales-orders/{sales_order}/pdf', [SalesOrderPdfController::class, 'show'])->name('sales-orders.pdf.show');

    Route::resource('production-orders', ProductionOrderController::class)->only(['index', 'show']);
    Route::controller(ProductionStepController::class)->prefix('production-steps/{step}')->name('production-steps.')->group(function (): void {
        Route::post('start', 'start')->name('start');
        Route::post('pause', 'pause')->name('pause');
        Route::post('complete', 'complete')->name('complete');
    });

    Route::resource('products', ProductController::class)->except('destroy');
    Route::resource('materials', MaterialController::class)->except('destroy');

    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    Route::post('materials/{material}/stock-movements', [StockMovementController::class, 'store'])->name('materials.stock-movements.store');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
