<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function range(Request $request): array
    {
        $start = $request->date('start_date') ?? now()->startOfMonth();
        $end = $request->date('end_date') ?? now();

        return [$start->copy()->startOfDay(), $end->copy()->endOfDay()];
    }

    public function index(Request $request)
    {
        [$start, $end] = $this->range($request);

        $sales = Sale::whereBetween('created_at', [$start, $end]);
        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end]);

        $cogs = (clone $items)->selectRaw('SUM(sale_items.quantity * COALESCE(sale_items.cost_at_sale, products.cost_price)) as cogs')->value('cogs') ?? 0;

        $returns = SaleReturn::whereBetween('created_at', [$start, $end]);
        $returnsTotal = (int) (clone $returns)->sum('total');
        $returnedCogs = (int) (SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'products.id', '=', 'sale_return_items.product_id')
            ->whereBetween('sale_returns.created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(sale_return_items.quantity * products.cost_price), 0) as cogs')
            ->value('cogs') ?? 0);

        $revenue = (int) (clone $sales)->sum('total') - $returnsTotal;
        $cogs = (int) $cogs - $returnedCogs;

        $summary = [
            'transactions' => (clone $sales)->count(),
            'gross' => (clone $sales)->sum('subtotal'),
            'discount' => (clone $sales)->sum('discount') + (clone $sales)->sum('voucher_discount'),
            'returns' => $returnsTotal,
            'net' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => (int) round($revenue - $cogs),
            'expenses' => Expense::whereBetween('spent_at', [$start, $end])->sum('amount'),
        ];
        $summary['net_profit'] = (int) round($summary['gross_profit'] - $summary['expenses']);

        $daily = (clone $sales)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as transactions, SUM(subtotal) as gross, SUM(discount) as discount, SUM(total) as revenue")
            ->groupBy('day')
            ->orderByDesc('day')
            ->get()
            ->map(function ($day) use ($start, $end) {
                $dayStart = Carbon::parse($day->day)->startOfDay();
                $dayEnd = Carbon::parse($day->day)->endOfDay();

                $day->cogs = (int) (SaleItem::query()
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->whereBetween('sales.created_at', [$dayStart, $dayEnd])
                    ->selectRaw('SUM(sale_items.quantity * COALESCE(sale_items.cost_at_sale, products.cost_price)) as cogs')
                    ->value('cogs') ?? 0);

                $day->expense = Expense::whereBetween('spent_at', [$dayStart, $dayEnd])->sum('amount');
                $day->returns = (int) SaleReturn::whereBetween('created_at', [$dayStart, $dayEnd])->sum('total');
                $day->returnedCogs = (int) (SaleReturnItem::query()
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                    ->whereBetween('sale_returns.created_at', [$dayStart, $dayEnd])
                    ->selectRaw('COALESCE(SUM(sale_return_items.quantity * products.cost_price), 0) as cogs')
                    ->value('cogs') ?? 0);

                $day->cogs -= $day->returnedCogs;
                $day->revenue -= $day->returns;
                $day->profit = (int) round($day->revenue - $day->cogs - $day->expense);

                return $day;
            });

        $topProducts = (clone $items)
            ->selectRaw('products.name, SUM(sale_items.quantity) as sold, SUM(sale_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold')
            ->limit(10)
            ->get();

        return view('reports.index', compact('summary', 'daily', 'topProducts', 'start', 'end'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->range($request);
        $filename = "laporan-{$start->format('Ymd')}-{$end->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($start, $end) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Laporan Kasirku', $start->format('d/m/Y'), $end->format('d/m/Y')]);
            fputcsv($out, []);

            fputcsv($out, ['Tanggal', 'Transaksi', 'Kotor', 'Diskon', 'Voucher', 'Retur', 'Bersih', 'HPP', 'Pengeluaran', 'Laba']);
            Sale::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as day, COUNT(*) as transactions, SUM(subtotal) as gross, SUM(discount) as discount, SUM(voucher_discount) as voucher, SUM(total) as revenue")
                ->groupBy('day')
                ->orderBy('day')
                ->each(function ($day) use ($out, $start, $end) {
                    $dayStart = Carbon::parse($day->day)->startOfDay();
                    $dayEnd = Carbon::parse($day->day)->endOfDay();

                    $cogs = (int) (SaleItem::query()
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->whereBetween('sales.created_at', [$dayStart, $dayEnd])
                        ->selectRaw('SUM(sale_items.quantity * COALESCE(sale_items.cost_at_sale, products.cost_price)) as cogs')
                        ->value('cogs') ?? 0);

                    $returns = (int) SaleReturn::whereBetween('created_at', [$dayStart, $dayEnd])->sum('total');
                    $returnedCogs = (int) (SaleReturnItem::query()
                        ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->whereBetween('sale_returns.created_at', [$dayStart, $dayEnd])
                        ->selectRaw('COALESCE(SUM(sale_return_items.quantity * products.cost_price), 0) as cogs')
                        ->value('cogs') ?? 0);
                    $cogs -= $returnedCogs;
                    $revenue = (int) $day->revenue - $returns;
                    $expense = Expense::whereBetween('spent_at', [$dayStart, $dayEnd])->sum('amount');

                    fputcsv($out, [
                        Carbon::parse($day->day)->format('d/m/Y'),
                        $day->transactions,
                        $day->gross,
                        $day->discount,
                        $day->voucher,
                        $returns,
                        $revenue,
                        $cogs,
                        $expense,
                        (int) round($revenue - $cogs - $expense),
                    ]);
                });

            fputcsv($out, []);

            fputcsv($out, ['Rincian Transaksi']);
            fputcsv($out, ['Invoice', 'Waktu', 'Pelanggan', 'Kasir', 'Metode', 'Kotor', 'Diskon', 'Voucher', 'Pajak', 'Total', 'Retur']);
            Sale::with('customer', 'user', 'returns')
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at')
                ->each(function ($sale) use ($out) {
                    fputcsv($out, [
                        $sale->invoice_number,
                        $sale->created_at->format('d/m/Y H:i'),
                        $sale->customer?->name ?? '-',
                        $sale->user?->name ?? '-',
                        $sale->payment_method,
                        $sale->subtotal,
                        $sale->discount,
                        $sale->voucher_discount,
                        $sale->tax,
                        $sale->total,
                        (int) $sale->returns->sum('total'),
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
