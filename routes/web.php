<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CashReportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerWalletController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Semua user (kasir + admin)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])
        ->middleware('throttle:60,1')
        ->name('pos.checkout');
    Route::post('/pos/hold', [PosController::class, 'hold'])
        ->middleware('throttle:60,1')
        ->name('pos.hold');
    Route::delete('/pos/hold/{heldSale}', [PosController::class, 'destroyHold'])->name('pos.hold.destroy');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/pay', [SaleController::class, 'pay'])
        ->middleware('throttle:30,1')
        ->name('sales.pay');
    Route::get('/sales/{sale}/return', [SaleReturnController::class, 'create'])->name('sales.return.create');
    Route::post('/sales/{sale}/return', [SaleReturnController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('sales.return.store');

    Route::get('/shifts', [CashShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [CashShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{shift}/close', [CashShiftController::class, 'close'])->name('shifts.close');

    // Versi aplikasi & pembaruan.
    Route::get('/updates', [UpdateController::class, 'whatsNew'])->name('updates.index');
    Route::get('/updates/status', [UpdateController::class, 'status'])->name('updates.status');
    Route::post('/updates/refresh', [UpdateController::class, 'refresh'])->name('updates.refresh');
    Route::post('/updates/apply', [UpdateController::class, 'apply'])->name('updates.apply');

    // Cek kode voucher dipakai kasir di halaman POS (BUG-16).
    Route::get('/vouchers/{code}/check', [VoucherController::class, 'check'])->name('vouchers.check');

    // Kartu produk (read-only, semua user)
    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product')
        ->name('products.show');

    // Khusus admin
    // X/Z report dipakai kasir saat tutup kas, begitu pula kartu pelanggan
    // untuk melihat kasbon, poin, dan riwayat belanja.
    Route::get('/reports/x', [CashReportController::class, 'xReport'])->name('reports.x');
    Route::get('/reports/z', [CashReportController::class, 'zReport'])->name('reports.z');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::middleware('admin')->group(function () {
        Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import.form');
        Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
        Route::post('/products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
        Route::post('/products/labels', [ProductController::class, 'labels'])->name('products.labels');

        Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('categories', CategoryController::class)->only(['store', 'destroy']);

        Route::get('/stocks', [StockMovementController::class, 'index'])->name('stocks.index');
        Route::post('/stocks', [StockMovementController::class, 'store'])->name('stocks.store');

        // Stok opname
        Route::get('/opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
        Route::get('/opnames/create', [StockOpnameController::class, 'create'])->name('opnames.create');
        Route::post('/opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
        Route::get('/opnames/{opname}', [StockOpnameController::class, 'show'])->name('opnames.show');
        Route::post('/opnames/{opname}/items', [StockOpnameController::class, 'updateItems'])->name('opnames.items.update');
        Route::post('/opnames/{opname}/finish', [StockOpnameController::class, 'finish'])->name('opnames.finish');
        Route::delete('/opnames/{opname}', [StockOpnameController::class, 'destroy'])->name('opnames.destroy');

        // Pembelian & supplier
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{purchase}/pay', [PurchaseController::class, 'pay'])->name('purchases.pay');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
        Route::get('/suppliers', [PurchaseController::class, 'suppliers'])->name('suppliers.index');
        Route::post('/suppliers', [PurchaseController::class, 'storeSupplier'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [PurchaseController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [PurchaseController::class, 'destroySupplier'])->name('suppliers.destroy');

        // Notifikasi
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('/customers/{customer}/prices', [CustomerController::class, 'storePrice'])->name('customers.prices.store');
        Route::delete('/customers/{customer}/prices/{customerPrice}', [CustomerController::class, 'destroyPrice'])->name('customers.prices.destroy');
        Route::post('/customers/{customer}/deposits', [CustomerWalletController::class, 'storeDeposit'])->name('customers.deposits.store');
        Route::post('/customers/{customer}/deposits/withdraw', [CustomerWalletController::class, 'storeWithdrawal'])->name('customers.deposits.withdraw');
        Route::post('/customers/{customer}/points', [CustomerWalletController::class, 'storePoints'])->name('customers.points.store');

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::get('/settings/backup', [BackupController::class, 'index'])->name('settings.backup.index');
        Route::post('/settings/backup', [BackupController::class, 'store'])->name('settings.backup.store');
        Route::post('/settings/backup/restore', [BackupController::class, 'restore'])->name('settings.backup.restore');
        Route::get('/settings/backup/{filename}', [BackupController::class, 'download'])->name('settings.backup.download');
        Route::delete('/settings/backup/{filename}', [BackupController::class, 'destroy'])->name('settings.backup.destroy');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
