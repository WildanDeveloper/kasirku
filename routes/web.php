<?php

use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'pos.index' : 'login'));

Route::get('/dashboard', fn () => redirect()->route(auth()->user()->is_admin ? 'reports.index' : 'pos.index'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Semua user (kasir + admin)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/hold', [PosController::class, 'hold'])->name('pos.hold');
    Route::delete('/pos/hold/{heldSale}', [PosController::class, 'destroyHold'])->name('pos.hold.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/pay', [SaleController::class, 'pay'])->name('sales.pay');

    Route::get('/shifts', [CashShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [CashShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{shift}/close', [CashShiftController::class, 'close'])->name('shifts.close');

    // Khusus admin
    Route::middleware('admin')->group(function () {
        Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import.form');
        Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
        Route::post('/products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');

        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->only(['store', 'destroy']);

        Route::get('/stocks', [StockMovementController::class, 'index'])->name('stocks.index');
        Route::post('/stocks', [StockMovementController::class, 'store'])->name('stocks.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
