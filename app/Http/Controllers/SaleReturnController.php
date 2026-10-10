<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function create(Sale $sale)
    {
        $this->authorizeTransaction($request = request(), $sale);

        $sale->load(['items.product', 'returns']);
        abort_if($sale->returnableItems()->isEmpty(), 422, 'Semua barang pada transaksi ini sudah diretur.');

        return view('sales.return', compact('sale'));
    }

    public function store(Request $request, Sale $sale)
    {
        $this->authorizeTransaction($request, $sale);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'refund_method' => ['required', 'in:cash,restock'],
            'reason' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Baris dengan jumlah 0 diabaikan (tidak diretur).
        $data['items'] = array_values(array_filter(
            $data['items'],
            fn ($row) => (int) $row['quantity'] > 0
        ));

        abort_if($data['items'] === [], 422, 'Pilih minimal satu barang untuk diretur.');

        $result = DB::transaction(function () use ($data, $sale, $request) {
            $sale->load('items');
            $lines = [];

            foreach ($data['items'] as $row) {
                $item = SaleItem::whereKey($row['sale_item_id'])->firstOrFail();
                abort_if($item->sale_id !== $sale->id, 422, 'Item tidak termasuk transaksi ini.');

                $available = $item->quantity - $item->returnedQuantity();
                abort_if($row['quantity'] > $available, 422, "Jumlah retur melebihi sisa yang bisa diretur ({$available}).");

                $lines[] = ['item' => $item, 'quantity' => (int) $row['quantity']];
            }

            $subtotal = 0;
            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'user_id' => $request->user()->id,
                'subtotal' => 0,
                'refund_method' => $data['refund_method'],
                'reason' => $data['reason'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $quantity = $line['quantity'];
                $refundSubtotal = (int) round($item->subtotal * $quantity / max(1, $item->quantity));
                $subtotal += $refundSubtotal;

                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'price' => $item->price,
                    'subtotal' => $refundSubtotal,
                ]);

                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                StockMovement::record($product, 'in', $quantity, 'Retur '.$sale->invoice_number, $request->user()->id);
            }

            // Bagi diskon, voucher, dan pajak secara proporsional terhadap nilai retur.
            $ratio = $sale->subtotal > 0 ? $subtotal / $sale->subtotal : 0;
            $discountShare = (int) round($sale->discount * $ratio);
            $voucherShare = (int) round($sale->voucher_discount * $ratio);
            $taxShare = (int) round($sale->tax * $ratio);
            $total = max(0, $subtotal - $discountShare - $voucherShare + $taxShare);

            $debtReduction = 0;
            if ($sale->is_credit && ! $sale->settled_at) {
                // Retur transaksi utang otomatis mengurangi sisa utang, bukan uang tunai.
                $debtReduction = min($total, $sale->remainingAmount());
            }

            $return->update([
                'subtotal' => $subtotal,
                'discount_share' => $discountShare,
                'voucher_share' => $voucherShare,
                'tax_share' => $taxShare,
                'total' => $total,
                'debt_reduction' => $debtReduction,
            ]);

            if ($debtReduction > 0) {
                $sale->increment('paid_amount', $debtReduction);

                if ($sale->remainingAmount() === 0) {
                    $sale->update(['settled_at' => now()]);
                }
            }

            // Catatan kas keluar untuk retur tunai ditangani CashShift::expectedCash()
            // supaya tidakdobel hitung dengan pengurangan pendapatan di laporan (BUG-21).
            return [$return, $total];
        });

        [$return, $total] = $result;

        $message = $return->refund_method === 'cash' && $return->debt_reduction === 0
            ? 'Retur tersimpan & uang Rp '.number_format($total, 0, ',', '.').' dikeluarkan.'
            : 'Retur tersimpan. Nilai retur: Rp '.number_format($total, 0, ',', '.').'.';
        if ($return->debt_reduction > 0) {
            $message = 'Retur tersimpan & mengurangi utang Rp '.number_format($return->debt_reduction, 0, ',', '.').'.';
        }

        ActivityLogger::record('sale.returned', $message, $sale, ['nilai' => $total, 'metode' => $data['refund_method']]);

        return redirect()->route('sales.show', $sale)->with('success', $message);
    }

    /** Kasir hanya boleh retur transaksinya sendiri; admin bebas. */
    private function authorizeTransaction(Request $request, Sale $sale): void
    {
        abort_unless(
            $sale->user_id === $request->user()->id || $request->user()->is_admin,
            403,
            'Transaksi ini milik kasir lain.'
        );
    }
}