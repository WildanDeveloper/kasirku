<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\HeldSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('barcode', 'like', "%{$s}%")))
            ->orderBy('name')
            ->limit(50)
            ->get();

        $customers = Customer::orderBy('name')->get();
        $taxPercent = (float) Setting::get('tax_percent');
        $openShift = CashShift::open()->where('user_id', $request->user()->id)->latest()->first();
        $heldSales = HeldSale::with('customer')->where('user_id', $request->user()->id)->latest()->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'items' => $h->items,
                'discount' => (int) $h->discount,
                'customer_id' => $h->customer_id,
                'note' => $h->note,
            ]);

        return view('pos.index', compact('products', 'customers', 'taxPercent', 'openShift', 'heldSales'));
    }

    public function hold(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $held = HeldSale::create([
            'user_id' => $request->user()->id,
            'customer_id' => $data['customer_id'] ?? null,
            'items' => $data['items'],
            'discount' => $data['discount'] ?? 0,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['message' => 'Transaksi ditahan.', 'id' => $held->id]);
    }

    public function destroyHold(Request $request, HeldSale $heldSale)
    {
        abort_unless($heldSale->user_id === $request->user()->id, 403);
        $heldSale->delete();

        return response()->json(['message' => 'Transaksi tertahan dihapus.']);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'apply_tax' => ['nullable', 'boolean'],
            'is_credit' => ['nullable', 'boolean'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
        ]);

        abort_if(
            ! CashShift::open()->where('user_id', $request->user()->id)->exists(),
            422,
            'Buka shift kasir terlebih dahulu.'
        );

        $sale = DB::transaction(function () use ($data, $request) {
            $subtotal = 0;

            $sale = Sale::create([
                'invoice_number' => Sale::nextInvoiceNumber(),
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
                'paid_amount' => $data['paid_amount'],
                'change_amount' => 0,
                'payment_method' => $data['payment_method'],
                'user_id' => $request->user()->id,
                'customer_id' => $data['customer_id'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                abort_if($product->stock < $item['quantity'], 422, "Stok {$product->name} tidak cukup.");

                $line = $product->selling_price * $item['quantity'];
                $subtotal += $line;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->selling_price,
                    'subtotal' => $line,
                ]);

                StockMovement::record($product, 'out', (int) $item['quantity'], 'Penjualan '.$sale->invoice_number, $request->user()->id);
            }

            $discount = min((int) ($data['discount'] ?? 0), $subtotal);
            $tax = 0;

            if (! empty($data['apply_tax']) && (float) Setting::get('tax_percent') > 0) {
                $tax = (int) round(($subtotal - $discount) * (float) Setting::get('tax_percent') / 100);
            }

            $total = $subtotal - $discount + $tax;

            $isCredit = ! empty($data['is_credit']) && ($data['customer_id'] ?? null) !== null;
            if ($isCredit) {
                abort_if($data['paid_amount'] >= $total, 422, 'Transaksi utang: uang bayar harus kurang dari total.');
            } else {
                abort_if($data['paid_amount'] < $total, 422, 'Uang bayar kurang dari total.');
            }

            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $data['paid_amount'],
                'change_amount' => $isCredit ? 0 : $data['paid_amount'] - $total,
                'is_credit' => $isCredit,
                'settled_at' => $isCredit ? null : now(),
            ]);

            return $sale;
        });

        return response()->json([
            'invoice_number' => $sale->invoice_number,
            'total' => $sale->total,
            'change_amount' => $sale->change_amount,
            'remaining' => $sale->remainingAmount(),
            'receipt_url' => route('sales.show', $sale),
        ]);
    }
}
