<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPrice;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::withCount('sales')
            ->withSum(['sales as outstanding_sum' => fn ($q) => $q->where('is_credit', true)->whereNull('settled_at')], DB::raw('total - paid_amount'))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $sales = $customer->sales()->with('user')->latest()->paginate(10);

        $stats = [
            'transactions' => $customer->sales()->count(),
            'total_spent' => (int) $customer->sales()->sum('total'),
            'outstanding' => $customer->outstanding(),
            'avg' => (int) ($customer->sales()->avg('total') ?? 0),
        ];

        $favorites = \App\Models\SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.customer_id', $customer->id)
            ->selectRaw('products.name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as spent')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $prices = $customer->prices()->with('product')->orderBy('product_id')->get();
        $deposits = $customer->deposits()->latest()->limit(10)->get();
        $loyalty = $customer->loyaltyTransactions()->latest()->limit(10)->get();
        $depositBalance = $customer->depositBalance();
        $pointValue = $customer->pointValue();

        return view('customers.show', compact(
            'customer',
            'sales',
            'stats',
            'favorites',
            'prices',
            'deposits',
            'loyalty',
            'depositBalance',
            'pointValue',
        ));
    }

    public function store(Request $request)
    {
        $customer = Customer::create($this->validated($request));

        ActivityLogger::record('customer.created', 'Menambah pelanggan '.$customer->name, $customer);

        return back()->with('success', 'Pelanggan ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request, $customer));

        ActivityLogger::record('customer.updated', 'Mengubah pelanggan '.$customer->name, $customer);

        return back()->with('success', 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        ActivityLogger::record('customer.deleted', 'Menghapus pelanggan '.$customer->name, $customer);

        $customer->delete();

        return back()->with('success', 'Pelanggan dihapus.');
    }

    /** Harga khusus produk untuk pelanggan ini. */
    public function storePrice(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'price' => ['required', 'integer', 'min:0'],
        ]);

        CustomerPrice::updateOrCreate(
            ['customer_id' => $customer->id, 'product_id' => $data['product_id']],
            ['price' => $data['price']],
        );

        return back()->with('success', 'Harga khusus disimpan.');
    }

    public function destroyPrice(Customer $customer, CustomerPrice $customerPrice)
    {
        abort_if($customerPrice->customer_id !== $customer->id, 404);
        $customerPrice->delete();

        return back()->with('success', 'Harga khusus dihapus.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_wholesale' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'phone' => ($data['phone'] ?? null) ?: null,
            'address' => ($data['address'] ?? null) ?: null,
            'is_wholesale' => (bool) ($data['is_wholesale'] ?? false),
        ];
    }
}