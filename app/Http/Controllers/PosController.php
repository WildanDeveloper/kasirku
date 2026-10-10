<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\HeldSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Voucher;
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
            ->limit(300)
            ->get();

        // Map harga khusus: "customerId:productId" => harga.
        // Dibentuk di PHP agar operatornya sama di SQLite/MySQL/PostgreSQL (BUG-28).
        $customPrices = CustomerPrice::whereIn('product_id', $products->pluck('id'))
            ->get(['customer_id', 'product_id', 'price'])
            ->mapWithKeys(fn ($cp) => [$cp->customer_id.':'.$cp->product_id => (int) $cp->price])
            ->all();

        $customers = Customer::orderBy('name')->get();
        $taxPercent = (float) Setting::get('tax_percent');
        $vouchers = Voucher::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('code')
            ->get(['code', 'name', 'type', 'value', 'min_purchase']);
        $openShift = CashShift::open()->where('user_id', $request->user()->id)->latest()->first();
        $heldSales = HeldSale::with('customer')->where('user_id', $request->user()->id)->latest()->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'items' => $h->items,
                'discount' => (int) $h->discount,
                'customer_id' => $h->customer_id,
                'note' => $h->note,
            ]);

        return view('pos.index', compact('products', 'customers', 'taxPercent', 'openShift', 'heldSales', 'customPrices', 'vouchers'));
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
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris,debit'],
        ]);

        abort_if(
            ! CashShift::open()->where('user_id', $request->user()->id)->exists(),
            422,
            'Buka shift kasir terlebih dahulu.'
        );

        $customer = ! empty($data['customer_id'])
            ? Customer::find($data['customer_id'])
            : null;

        $sale = DB::transaction(function () use ($data, $request, $customer) {
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
                $product = Product::with('customerPrices')
                    ->whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_if($product->stock < $item['quantity'], 422, "Stok {$product->name} tidak cukup.");

                // Harga mengikuti jenis pelanggan: harga khusus > grosir > ecer.
                $price = $product->priceFor($customer);
                $line = $price * $item['quantity'];
                $subtotal += $line;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $line,
                ]);

                StockMovement::record($product, 'out', (int) $item['quantity'], 'Penjualan '.$sale->invoice_number, $request->user()->id);
            }

            $discount = min((int) ($data['discount'] ?? 0), $subtotal);

            $voucher = null;
            $voucherDiscount = 0;
            if (! empty($data['voucher_code'])) {
                $voucher = Voucher::whereRaw('LOWER(code) = ?', [strtolower($data['voucher_code'])])
                    ->lockForUpdate()
                    ->first();

                abort_if(! $voucher, 422, 'Kode voucher tidak ditemukan.');
                abort_if(! $voucher->isValidNow(), 422, 'Voucher '.$voucher->statusLabel().'.');

                $voucherDiscount = $voucher->discountFor(max(0, $subtotal - $discount));
                abort_if($voucherDiscount <= 0, 422, 'Voucher tidak berlaku untuk belanja ini.');
            }

            $tax = 0;

            if (! empty($data['apply_tax']) && (float) Setting::get('tax_percent') > 0) {
                $tax = (int) round(($subtotal - $discount - $voucherDiscount) * (float) Setting::get('tax_percent') / 100);
            }

            $total = $subtotal - $discount - $voucherDiscount + $tax;

            // Jaring pengaman: total transaksi tidak boleh pernah negatif (BUG-07).
            abort_if($total < 0, 422, 'Total transaksi tidak valid.');

            $isCredit = ! empty($data['is_credit']) && ($data['customer_id'] ?? null) !== null;
            if ($isCredit) {
                abort_if($data['paid_amount'] >= $total, 422, 'Transaksi utang: uang bayar harus kurang dari total.');
            } else {
                abort_if($data['paid_amount'] < $total, 422, 'Uang bayar kurang dari total.');
            }

            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'voucher_id' => $voucher?->id,
                'voucher_discount' => $voucherDiscount,
                'tax' => $tax,
                'total' => $total,
                'paid_amount' => $data['paid_amount'],
                'change_amount' => $isCredit ? 0 : $data['paid_amount'] - $total,
                'is_credit' => $isCredit,
                'settled_at' => $isCredit ? null : now(),
            ]);

            $voucher?->increment('used_count');

            return $sale;
        });

        return response()->json([
            'invoice_number' => $sale->invoice_number,
            'total' => $sale->total,
            'discount' => $sale->discount,
            'voucher_discount' => $sale->voucher_discount,
            'change_amount' => $sale->change_amount,
            'remaining' => $sale->remainingAmount(),
            'receipt_url' => route('sales.show', $sale),
        ]);
    }
}
