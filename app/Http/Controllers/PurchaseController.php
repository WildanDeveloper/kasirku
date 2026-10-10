<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'user'])
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->date('date'), fn ($q, $d) => $q->whereDate('purchase_date', $d))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $outstanding = (int) Purchase::where('status', 'received')->sum(\DB::raw('total - paid_amount'));

        return view('purchases.index', compact('purchases', 'suppliers', 'outstanding'));
    }

    public function create()
    {
        return view('purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'barcode', 'cost_price', 'selling_price', 'unit']),
            'purchase' => new Purchase(['purchase_date' => today()->toDateString()]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'integer', 'min:0'],
        ]);

        $purchase = DB::transaction(function () use ($data, $request) {
            $purchase = Purchase::create([
                'invoice_number' => $data['invoice_number'] ?? Purchase::nextInvoiceNumber(),
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => 0,
                'discount' => (int) ($data['discount'] ?? 0),
                'total' => 0,
                'paid_amount' => (int) ($data['paid_amount'] ?? 0),
                'status' => 'received',
                'note' => $data['note'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $row) {
                $product = Product::whereKey($row['product_id'])->lockForUpdate()->firstOrFail();
                $quantity = (int) $row['quantity'];
                $cost = (int) $row['cost_price'];
                $line = $quantity * $cost;
                $subtotal += $line;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost_price' => $cost,
                    'subtotal' => $line,
                ]);

                // Barang masuk: stok naik dan harga beli produk diperbarui.
                StockMovement::record($product, 'in', $quantity, 'Pembelian '.$purchase->invoice_number, $request->user()->id);
                $product->update(['cost_price' => $cost]);
            }

            $total = max(0, $subtotal - (int) ($data['discount'] ?? 0));
            $purchase->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'paid_amount' => min((int) ($data['paid_amount'] ?? 0), $total),
            ]);

            if ($purchase->paid_amount > 0) {
                SupplierPayment::create([
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'amount' => $purchase->paid_amount,
                    'payment_method' => Setting::get('purchase_payment_method') === 'transfer' ? 'transfer' : 'cash',
                    'note' => 'Pembayaran saat pembelian',
                    'user_id' => $request->user()->id,
                ]);
            }

            return $purchase;
        });

        ActivityLogger::record('purchase.created', 'Pembelian '.$purchase->invoice_number.' Rp '.number_format($purchase->total, 0, ',', '.'), $purchase);

        return redirect()->route('purchases.index')->with('success', 'Pembelian tersimpan, stok bertambah '.collect($data['items'])->sum('quantity').' item.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier', 'payments.user']);

        return view('purchases.show', compact('purchase'));
    }

    public function pay(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        abort_if($purchase->status === 'cancelled', 422, 'Pembelian ini dibatalkan.');
        abort_if($purchase->outstanding() === 0, 422, 'Pembelian ini sudah lunas.');

        $amount = min($data['amount'], $purchase->outstanding());

        DB::transaction(function () use ($purchase, $data, $amount, $request) {
            SupplierPayment::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'note' => $data['note'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            $purchase->increment('paid_amount', $amount);
        });

        ActivityLogger::record('purchase.paid', 'Bayar supplier '.$purchase->invoice_number.' Rp '.number_format($amount, 0, ',', '.'), $purchase);

        return back()->with('success', 'Pembayaran supplier dicatat Rp '.number_format($amount, 0, ',', '.').'.');
    }

    public function cancel(Purchase $purchase)
    {
        abort_if($purchase->status === 'cancelled', 422, 'Pembelian sudah dibatalkan.');
        abort_if($purchase->paid_amount > 0, 422, 'Tidak bisa membatalkan pembelian yang sudah dibayar.');

        // Kembalikan stok.
        foreach ($purchase->items as $item) {
            StockMovement::record($item->product, 'out', $item->quantity, 'Pembatalan pembelian '.$purchase->invoice_number, auth()->id());
        }

        $purchase->update(['status' => 'cancelled']);

        ActivityLogger::record('purchase.cancelled', 'Membatalkan pembelian '.$purchase->invoice_number, $purchase);

        return back()->with('success', 'Pembelian dibatalkan dan stok dikembalikan.');
    }

    // ---------- Supplier ----------

    public function suppliers()
    {
        $suppliers = Supplier::withCount('purchases')
            ->withSum(['purchases as outstanding' => fn ($q) => $q->where('status', 'received')], DB::raw('total - paid_amount'))
            ->orderBy('name')
            ->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $supplier = Supplier::create($data);

        ActivityLogger::record('supplier.created', 'Menambah supplier '.$supplier->name, $supplier);

        return back()->with('success', 'Supplier ditambahkan.');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]));

        return back()->with('success', 'Supplier diperbarui.');
    }

    public function destroySupplier(Supplier $supplier)
    {
        abort_if($supplier->purchases()->exists(), 422, 'Supplier sudah punya riwayat pembelian.');

        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }
}
