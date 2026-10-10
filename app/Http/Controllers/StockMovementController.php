<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = StockMovement::with(['product', 'user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $lowStock = Product::whereColumn('stock', '<=', 'min_stock')->orderBy('stock')->get();

        // Hanya produk yang cocok pencarian (maksimal 50), bukan seluruh katalog (BUG-45).
        $search = trim((string) $request->query('product_search', ''));

        $products = Product::when($search !== '', fn ($q) => $q->where(fn ($w) => $w
            ->where('name', 'like', "%{$search}%")
            ->orWhere('barcode', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return view('stocks.index', compact('movements', 'lowStock', 'products', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        StockMovement::record(
            Product::findOrFail($data['product_id']),
            $data['type'],
            (int) $data['quantity'],
            ($data['note'] ?? null),
            $request->user()->id,
        );

        return redirect()->route('stocks.index')->with('success', 'Stok diperbarui.');
    }
}
