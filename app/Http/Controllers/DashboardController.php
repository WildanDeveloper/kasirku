<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $isAdmin = $user->is_admin;

        $salesQuery = fn (?Carbon $from = null, ?Carbon $to = null) => Sale::query()
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));

        $todaySales = $salesQuery($today, $todayEnd);

        $todayRevenue = (clone $todaySales)->sum('total');
        $todayTransactions = (clone $todaySales)->count();
        $todayCogs = $this->cogs($today, $todayEnd);
        $todayProfit = $todayRevenue - $todayCogs;

        $monthStart = now()->startOfMonth();
        $monthRevenue = (clone $salesQuery($monthStart, $todayEnd))->sum('total');
        $monthExpenses = Expense::whereBetween('spent_at', [$monthStart, $todayEnd])->sum('amount');

        $outstanding = Sale::where('is_credit', true)->whereNull('settled_at')
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as sisa')
            ->value('sisa') ?? 0;

        $lowStock = Product::whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(8)
            ->get();

        $last7 = collect(range(6, 0))->map(function ($daysAgo) {
            $day = now()->subDays($daysAgo);
            $from = $day->copy()->startOfDay();
            $to = $day->copy()->endOfDay();

            return [
                'label' => $day->translatedFormat('D, d M'),
                'revenue' => (int) Sale::whereBetween('created_at', [$from, $to])->sum('total'),
                'profit' => (int) Sale::whereBetween('created_at', [$from, $to])->sum('total') - $this->cogs($from, $to),
            ];
        });

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$monthStart, $todayEnd])
            ->selectRaw('products.name, SUM(sale_items.quantity) as sold, SUM(sale_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        $recentSales = Sale::with(['user', 'customer'])
            ->when(! $isAdmin, fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->limit(8)
            ->get();

        $openShift = CashShift::open()->where('user_id', $user->id)->latest()->first();
        $monthReturns = SaleReturn::whereBetween('created_at', [$monthStart, $todayEnd])->sum('total');
        $productCount = Product::count();

        return view('dashboard', compact(
            'todayRevenue',
            'todayTransactions',
            'todayProfit',
            'monthRevenue',
            'monthExpenses',
            'outstanding',
            'lowStock',
            'last7',
            'topProducts',
            'recentSales',
            'openShift',
            'monthReturns',
            'productCount',
            'isAdmin',
        ));
    }

    private function cogs(Carbon $from, Carbon $to): int
    {
        return (int) (SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(sale_items.quantity * products.cost_price), 0) as cogs')
            ->value('cogs') ?? 0);
    }
}