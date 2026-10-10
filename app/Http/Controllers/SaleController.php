<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $sales = Sale::with(['user', 'customer', 'returns'])
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
        $sale->load(['items.product', 'user', 'customer', 'payments.user', 'voucher', 'returns.user', 'returns.items.product']);

        return view('sales.show', compact('sale'));
    }

    public function pay(Request $request, Sale $sale)
    {
        $this->authorizeTransaction($request, $sale);

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
                'payment_method' => $data['payment_method'],
                'user_id' => $request->user()->id,
            ]);

            $sale->increment('paid_amount', $amount);

            if ($sale->paid_amount >= $sale->total) {
                $sale->update(['settled_at' => now()]);
            }
        });

        ActivityLogger::record('sale.paid', 'Bayar utang '.$sale->invoice_number.' sebesar Rp '.number_format($amount, 0, ',', '.'), $sale);

        return back()->with('success', 'Pembayaran utang dicatat: Rp '.number_format($amount, 0, ',', '.').'.');
    }

    /** Kasir hanya boleh menyentuh transaksinya sendiri; admin bebas. */
    private function authorizeTransaction(Request $request, Sale $sale): void
    {
        abort_unless(
            $sale->user_id === $request->user()->id || $request->user()->is_admin,
            403,
            'Transaksi ini milik kasir lain.'
        );
    }
}
