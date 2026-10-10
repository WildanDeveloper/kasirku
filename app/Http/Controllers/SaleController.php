<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with(['user', 'customer'])
            ->when($request->date, fn ($q, $d) => $q->whereDate('created_at', $d))
            ->when($request->credit === '1', fn ($q) => $q->where('is_credit', true)->whereNull('settled_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $outstanding = Sale::where('is_credit', true)->whereNull('settled_at')
            ->selectRaw('SUM(total - paid_amount) as outstanding')
            ->value('outstanding') ?? 0;

        return view('sales.index', compact('sales', 'outstanding'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user', 'customer', 'payments.user']);

        return view('sales.show', compact('sale'));
    }

    public function pay(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
        ]);

        $remaining = $sale->remainingAmount();
        abort_if($remaining === 0, 422, 'Transaksi sudah lunas.');

        $amount = min($data['amount'], $remaining);

        DB::transaction(function () use ($sale, $data, $amount, $request) {
            SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $amount,
                'user_id' => $request->user()->id,
            ]);

            $sale->increment('paid_amount', $amount);

            if ($sale->paid_amount >= $sale->total) {
                $sale->update(['settled_at' => now()]);
            }
        });

        return back()->with('success', 'Pembayaran utang dicatat: Rp '.number_format($amount, 0, ',', '.').'.');
    }
}
