<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::withCount('sales')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        Customer::create($this->validated($request));

        return back()->with('success', 'Pelanggan ditambahkan.');
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request, $customer));

        return back()->with('success', 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('success', 'Pelanggan dihapus.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
