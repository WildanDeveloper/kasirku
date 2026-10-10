<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CashReportController extends Controller
{
    /**
     * X-Report: rekap penjualan per kasir untuk satu tanggal (bisa dicetak ulang).
     */
    public function xReport(Request $request)
    {
        [$date, $data] = $this->build($request);

        return view('reports.x-report', compact('date', 'data'));
    }

    /**
     * Z-Report: ringkasan tutup kas harian + daftar shift.
     */
    public function zReport(Request $request)
    {
        [$date, $data] = $this->build($request);

        return view('reports.z-report', [
            'date' => $date,
            'data' => $data,
            'shifts' => CashShift::with('user')
                ->whereBetween('opened_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                ->latest('opened_at')
                ->get(),
        ]);
    }

    /** Rekap harian + per kasir untuk tanggal tertentu. */
    private function build(Request $request): array
    {
        $date = $request->date('date') ?: today();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $sales = Sale::whereBetween('sales.created_at', [$from, $to]);

        $data = [
            'transactions' => (clone $sales)->count(),
            'gross' => (int) (clone $sales)->sum('sales.subtotal'),
            'discount' => (int) (clone $sales)->sum('sales.discount') + (int) (clone $sales)->sum('sales.voucher_discount'),
            'revenue' => (int) (clone $sales)->sum('sales.total'),
            'cogs' => (int) $this->cogs($from, $to),
            'returns' => (int) SaleReturn::whereBetween('sale_returns.created_at', [$from, $to])->sum('sale_returns.total'),
            'credit' => (int) (clone $sales)->where('sales.is_credit', true)->sum('sales.total'),
            'credit_paid' => (int) (clone $sales)->where('sales.is_credit', true)->sum('sales.paid_amount'),
        ];
        $data['net'] = $data['revenue'] - $data['returns'];
        $data['profit'] = $data['net'] - $data['cogs'];

        $byCashier = User::whereHas('sales', fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->get()
            ->map(function (User $user) use ($from, $to) {
                $sales = Sale::where('sales.user_id', $user->id)->whereBetween('sales.created_at', [$from, $to]);

                $revenue = (int) $sales->sum('sales.total');
                $cogs = (int) (Sale::where('sales.user_id', $user->id)
                    ->whereBetween('sales.created_at', [$from, $to])
                    ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->selectRaw('COALESCE(SUM(sale_items.quantity * COALESCE(sale_items.cost_at_sale, products.cost_price)), 0) as cogs')
                    ->value('cogs') ?? 0);

                $methods = Sale::where('sales.user_id', $user->id)
                    ->whereBetween('sales.created_at', [$from, $to])
                    ->selectRaw('sales.payment_method, COUNT(*) as trx, SUM(sales.total) as total')
                    ->groupBy('sales.payment_method')
                    ->get()
                    ->keyBy('payment_method');

                return [
                    'user' => $user,
                    'transactions' => (clone $sales)->count(),
                    'revenue' => $revenue,
                    'cash' => (int) ($methods['cash']->total ?? 0),
                    'non_cash' => (int) $methods->where('payment_method', '!=', 'cash')->sum('total'),
                    'profit' => $revenue - $cogs,
                ];
            })
            ->sortByDesc('revenue');

        $data['by_cashier'] = $byCashier;

        return [$date, $data];
    }

    private function cogs(Carbon $from, Carbon $to): int
    {
        return (int) (SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(sale_items.quantity * COALESCE(sale_items.cost_at_sale, products.cost_price)), 0) as cogs')
            ->value('cogs') ?? 0);
    }
}