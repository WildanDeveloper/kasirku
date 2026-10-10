<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index()
    {
        $opnames = StockOpname::with('user')->withCount('items')->latest()->paginate(15);

        return view('opnames.index', compact('opnames'));
    }

    public function create()
    {
        return view('opnames.create', ['opname' => new StockOpname(['name' => 'Opname '.now()->format('d/m/Y H:i')])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'only_low' => ['nullable', 'boolean'],
        ]);

        $products = Product::when($data['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($data['only_low'] ?? null, fn ($q) => $q->whereColumn('stock', '<=', 'min_stock'))
            ->orderBy('name')
            ->get();

        abort_if($products->isEmpty(), 422, 'Tidak ada produk untuk diopname (pilih kategori atau tampilkan semua).');

        $opname = StockOpname::create([
            'name' => $data['name'] ?: 'Opname '.now()->format('d/m/Y H:i'),
            'note' => $data['note'] ?? null,
            'status' => 'draft',
            'user_id' => $request->user()->id,
        ]);

        foreach ($products as $product) {
            $opname->items()->create([
                'product_id' => $product->id,
                'system_qty' => $product->stock,
                'physical_qty' => null,
                'difference' => 0,
            ]);
        }

        return redirect()->route('opnames.show', $opname)->with('success', $products->count().' produk masuk daftar hitungan.');
    }

    public function show(StockOpname $opname)
    {
        $opname->load(['items.product', 'user']);

        return view('opnames.show', compact('opname'));
    }

    /** Simpan hasil hitungan fisik (bisa sebagian, kolom yang kosong dilewati). */
    public function updateItems(Request $request, StockOpname $opname)
    {
        abort_if($opname->isFinished(), 422, 'Opname sudah selesai dan tidak bisa diubah.');

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $items = $opname->items()->get()->keyBy('id');

        foreach ($data['items'] as $id => $quantity) {
            if ($quantity === null || $quantity === '') {
                continue;
            }

            $item = $items->get((int) $id);

            if (! $item) {
                continue;
            }

            $item->update([
                'physical_qty' => (int) $quantity,
                'difference' => (int) $quantity - $item->system_qty,
            ]);
        }

        return back()->with('success', 'Hasil hitungan disimpan.');
    }

    public function finish(Request $request, StockOpname $opname)
    {
        abort_if($opname->isFinished(), 422, 'Opname sudah selesai.');

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $pending = $opname->items()->whereNull('physical_qty')->count();
        if ($pending > 0) {
            return back()->with('warning', 'Masih ada '.$pending.' produk yang belum dihitung.');
        }

        $value = DB::transaction(function () use ($opname, $request) {
            $total = 0;

            foreach ($opname->items()->with('product')->get() as $item) {
                $difference = $item->physical_qty - $item->system_qty;

                if ($difference === 0) {
                    continue;
                }

                // Selisih opname dicatat sebagai penyesuaian stok resmi.
                StockMovement::record(
                    $item->product,
                    'adjustment',
                    $item->physical_qty,
                    'Opname '.$opname->name,
                    $request->user()->id,
                );

                $total += $difference * (int) $item->product->cost_price;
            }

            return $total;
        });

        $opname->update([
            'status' => 'finished',
            'finished_at' => now(),
            'difference_value' => $value,
            'note' => $data['note'] ?? $opname->note,
        ]);

        ActivityLogger::record(
            'opname.finished',
            'Menyelesaikan opname '.$opname->name.' (selisih nilai Rp '.number_format($value, 0, ',', '.').')',
            $opname
        );

        return redirect()->route('opnames.show', $opname)
            ->with('success', 'Opname selesai. Selisih nilai Rp '.number_format($value, 0, ',', '.').'.');
    }

    public function destroy(StockOpname $opname)
    {
        abort_if($opname->isFinished(), 422, 'Opname yang sudah selesai tidak bisa dihapus.');

        $opname->delete();

        return back()->with('success', 'Opname dihapus.');
    }
}