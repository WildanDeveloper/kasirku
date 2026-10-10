<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('barcode', 'like', "%{$s}%")))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.create', [
            'categories' => Category::orderBy('name')->get(),
            'product' => new Product(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'categories' => Category::orderBy('name')->get(),
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'customerPrices.customer']);

        $sold = (int) $product->saleItems()->sum('quantity');
        $revenue = (int) $product->saleItems()->sum('subtotal');
        $returned = (int) $product->returnItems()->sum('quantity');

        $recentSales = $product->saleItems()
            ->with('sale.user', 'sale.customer')
            ->latest()
            ->limit(15)
            ->get();

        $movements = $product->stockMovements()->with('user')->latest('id')->limit(20)->get();

        $potentialProfit = $product->saleItems()
            ->selectRaw('COALESCE(SUM(sale_items.subtotal - sale_items.quantity * products.cost_price), 0) as profit')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->value('profit');

        return view('products.show', compact('product', 'sold', 'revenue', 'returned', 'recentSales', 'movements', 'potentialProfit'));
    }

    /** Cetak label harga untuk produk terpilih (bulk). */
    public function labels(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'exists:products,id'],
            'mode' => ['nullable', 'in:retail,wholesale'],
        ]);

        $products = Product::with('category')->whereKey($data['ids'])->get();

        if ($products->isEmpty()) {
            return back()->with('warning', 'Tidak ada produk terpilih.');
        }

        $shop = [
            'name' => Setting::get('store_name'),
            'address' => Setting::get('store_address'),
            'phone' => Setting::get('store_phone'),
        ];

        $mode = $data['mode'] ?? 'retail';

        return view('products.labels', compact('products', 'shop', 'mode'));
    }

    public function importForm()
    {
        return view('products.import', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:400000'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', trim($validated['source']));
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (isset($lines[0]) && str_starts_with(strtolower($lines[0]), 'barcode')) {
            array_shift($lines);
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $userId = $request->user()->id;

        DB::transaction(function () use ($lines, &$created, &$updated, &$errors, $userId) {
            foreach ($lines as $index => $line) {
                $lineNumber = $index + 1;

                // Deteksi pemisah otomatis: tab > titik koma > koma.
                $separator = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');
                $cells = array_map(fn ($c) => trim(str_replace('"', '', $c)), explode($separator, $line));

                $name = $cells[1] ?? '';
                if ($name === '') {
                    $errors[] = "Baris {$lineNumber}: nama produk kosong.";
                    continue;
                }

                // Kolom kategori opsional: sel ketiga non-angka = nama kategori.
                $category = null;
                $cell2 = $cells[2] ?? '';
                if ($cell2 !== '' && ! ctype_digit($cell2)) {
                    $category = Category::firstWhere(DB::raw('LOWER(name)'), strtolower($cell2));
                    $base = 3;
                } else {
                    $base = 2;
                }

                $digits = fn ($value) => max(0, (int) preg_replace('/\D/', '', $value ?? '0'));

                $barcode = ($cells[0] ?? '') ?: null;

                $row = [
                    'barcode' => $barcode,
                    'name' => $name,
                    'category_id' => $category?->id,
                    'cost_price' => $digits($cells[$base] ?? null),
                    'selling_price' => $digits($cells[$base + 1] ?? null),
                    'unit' => trim((string) ($cells[$base + 3] ?? '')) ?: 'pcs',
                    'min_stock' => ($cells[$base + 4] ?? '') !== '' ? $digits($cells[$base + 4]) : 5,
                ];

                $existing = null;
                if ($barcode !== null) {
                    $existing = Product::where('barcode', $barcode)->first();
                } elseif (Product::where('name', $name)->exists()) {
                    $existing = Product::where('name', $name)->first();
                }

                if ($existing) {
                    $existing->update(collect($row)->except('barcode')->all());

                    $stockCell = $cells[$base + 2] ?? '';
                    if ($stockCell !== '' && $digits($stockCell) !== $existing->stock) {
                        StockMovement::record($existing, 'adjustment', $digits($stockCell), 'Import massal', $userId);
                    }

                    $updated++;
                    continue;
                }

                $product = Product::create($row);

                $initialStock = $digits($cells[$base + 2] ?? null);
                if ($initialStock > 0) {
                    StockMovement::record($product, 'in', $initialStock, 'Import massal', $userId);
                }

                $created++;
            }
        });

        $message = "Import selesai: {$created} produk baru, {$updated} diperbarui.";
        if ($errors !== []) {
            $message .= ' Gagal: '.count($errors).' baris. '.implode(' ', array_slice($errors, 0, 5));
        }

        return redirect()->route('products.index')->with($errors === [] ? 'success' : 'warning', $message);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', 'in:category,price_percent,price_set,restock,delete'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'percent' => ['nullable', 'integer', 'min:-99', 'max:1000'],
            'price' => ['nullable', 'integer', 'min:0'],
            'stock_delta' => ['nullable', 'integer', 'min:-100000', 'max:100000'],
        ]);

        $products = Product::whereKey($data['ids'])->get();
        $userId = $request->user()->id;

        $message = match ($data['action']) {
            'category' => (function () use ($data, $products) {
                $products->each->update(['category_id' => $data['category_id'] ?? null]);

                return 'Kategori diperbarui untuk '.count($products).' produk.';
            })(),
            'price_percent' => (function () use ($data, $products) {
                $factor = 1 + $data['percent'] / 100;
                $products->each(function (Product $product) use ($factor) {
                    $product->update([
                        'selling_price' => max(0, (int) round($product->selling_price * $factor)),
                    ]);
                });

                return 'Harga jual '.count($products).' produk diubah '.($data['percent'] >= 0 ? '+' : '').$data['percent'].'%.';
            })(),
            'price_set' => (function () use ($data, $products) {
                $products->each->update(['selling_price' => $data['price']]);

                return 'Harga jual '.count($products).' produk di-set Rp '.number_format($data['price'], 0, ',', '.').'.';
            })(),
            'restock' => (function () use ($data, $products, $userId) {
                $delta = $data['stock_delta'];

                $products->each(function (Product $product) use ($delta, $userId) {
                    StockMovement::record(
                        $product,
                        $delta >= 0 ? 'in' : 'out',
                        abs($delta),
                        'Restock massal',
                        $userId,
                    );
                });

                return 'Stok '.count($products).' produk diubah '.($delta >= 0 ? '+' : '').$delta.'.';
            })(),
            'delete' => (function () use ($products) {
                $deletable = $products->reject(fn (Product $p) => $p->saleItems()->exists());
                $skipped = count($products) - count($deletable);

                $deletable->each->delete();

                return 'Menghapus '.count($deletable).' produk'.
                    ($skipped > 0 ? ", {$skipped} dilewati (ada riwayat penjualan)." : '.');
            })(),
        };

        return redirect()
            ->route('products.index')
            ->with($data['action'] === 'delete' && isset($skipped) && $skipped > 0 ? 'warning' : 'success', $message);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'.($product ? ','.$product->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'price_wholesale' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
        ]);

        $data['barcode'] = $data['barcode'] ?: null;
        $data['category_id'] = $data['category_id'] ?? null;
        $data['price_wholesale'] = ($data['price_wholesale'] ?? null) !== null && $data['price_wholesale'] !== ''
            ? (int) $data['price_wholesale']
            : null;

        return $data;
    }
}
