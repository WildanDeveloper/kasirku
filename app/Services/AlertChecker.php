<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\CashShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Membuat notifikasi dalam aplikasi untuk kondisi yang perlu diawasi:
 * stok menipis, shift kas menggantung, dan utang yang sudah lama.
 *
 * Semua pemeriksaan aman dipanggil berulang (dari scheduler maupun dari
 * halaman dashboard) karena AppNotification::notify() menahan pesan serupa
 * selama 6 jam.
 */
class AlertChecker
{
    public function checkLowStock(): int
    {
        if (! Setting::get('low_stock_alert')) {
            return 0;
        }

        $low = Product::whereColumn('stock', '<=', 'min_stock')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit(20)
            ->get();

        $count = 0;

        foreach ($low as $product) {
            AppNotification::notify(
                'low_stock',
                'Stok menipis: '.$product->name,
                'Sisa '.$product->stock.' '.$product->unit.' (minimum '.$product->min_stock.').',
                route('products.index'),
                'warning',
            );
            $count++;
        }

        if ($low->isEmpty()) {
            return 0;
        }

        AppNotification::notify(
            'low_stock_summary',
            $low->count().' produk stok menipis',
            $low->take(3)->pluck('name')->implode(', ').($low->count() > 3 ? ', …' : ''),
            route('products.index'),
            'warning',
        );

        return $count;
    }

    /** Shift yang dibuka lebih dari 12 jam lalu belum ditutup. */
    public function checkOpenShifts(): int
    {
        $stale = CashShift::whereNull('closed_at')
            ->where('opened_at', '<=', now()->subHours(12))
            ->with('user')
            ->get();

        foreach ($stale as $shift) {
            AppNotification::notify(
                'shift_open',
                'Shift kas belum ditutup',
                ($shift->user?->name ?? 'Kasir').' membuka shift '.$shift->opened_at->format('d/m H:i').' dan belum menutup.',
                route('shifts.index'),
                'warning',
            );
        }

        return $stale->count();
    }

    /** Utang pelanggan yang sudah lewat 30 hari. */
    public function checkOverdueCredit(): int
    {
        $overdue = Sale::where('is_credit', true)
            ->whereNull('settled_at')
            ->where('created_at', '<=', now()->subDays(30))
            ->with('customer')
            ->get()
            ->filter(fn (Sale $sale) => $sale->remainingAmount() > 0);

        foreach ($overdue as $sale) {
            $days = (int) $sale->created_at->diffInDays(now());

            AppNotification::notify(
                'credit_overdue',
                'Utang pelanggan belum lunas',
                ($sale->customer?->name ?? 'Pelanggan').' punya utang Rp '.number_format($sale->remainingAmount(), 0, ',', '.').' ('.$days.' hari, '. $sale->invoice_number .').',
                route('customers.index'),
                'danger',
            );
        }

        return $overdue->count();
    }

    /** Utang supplier yang sudah lewat 30 hari. */
    public function checkOverdueSuppliers(): int
    {
        $suppliers = Supplier::query()
            ->withSum(['purchases as outstanding' => fn ($q) => $q->where('status', 'received')], DB::raw('total - paid_amount'))
            ->get()
            ->filter(fn (Supplier $supplier) => (int) $supplier->outstanding > 0);

        foreach ($suppliers as $supplier) {
            AppNotification::notify(
                'supplier_debt',
                'Utang supplier: '.$supplier->name,
                'Sisa Rp '.number_format((int) $supplier->outstanding, 0, ',', '.').' belum dibayar.',
                route('purchases.index'),
                'warning',
            );
        }

        return $suppliers->count();
    }

    /** Jalankan semua pemeriksaan. */
    public function run(): array
    {
        return [
            'low_stock' => $this->checkLowStock(),
            'open_shifts' => $this->checkOpenShifts(),
            'credit_overdue' => $this->checkOverdueCredit(),
            'supplier_debt' => $this->checkOverdueSuppliers(),
        ];
    }
}