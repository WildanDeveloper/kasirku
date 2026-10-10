<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::withCount('sales')->latest()->paginate(20);

        return view('vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        Voucher::create($this->validated($request));

        return back()->with('success', 'Voucher dibuat.');
    }

    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($this->validated($request, $voucher));

        return back()->with('success', 'Voucher diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();

        return back()->with('success', 'Voucher dihapus.');
    }

    /** Dipakai POS untuk cek kode voucher sebelum checkout. */
    public function check(Request $request, string $code)
    {
        $subtotal = (int) $request->integer('subtotal', 0);
        $voucher = Voucher::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        abort_if(! $voucher, 404, 'Kode voucher tidak ditemukan.');
        abort_if(! $voucher->isValidNow(), 422, 'Voucher '.$voucher->statusLabel().'.');
        abort_if($subtotal < $voucher->min_purchase, 422, 'Minimum belanja Rp '.number_format($voucher->min_purchase, 0, ',', '.').'.');

        return response()->json([
            'code' => $voucher->code,
            'name' => $voucher->name,
            'label' => $voucher->label(),
            'discount' => $voucher->discountFor($subtotal),
        ]);
    }

    private function validated(Request $request, ?Voucher $voucher = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vouchers,code'.($voucher ? ','.$voucher->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'min_purchase' => ['nullable', 'integer', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => (int) $data['value'],
            'min_purchase' => (int) ($data['min_purchase'] ?? 0),
            'quota' => ($data['quota'] ?? null) !== null && ($data['quota'] ?? '') !== '' ? (int) $data['quota'] : null,
            'starts_at' => ($data['starts_at'] ?? null) ?: null,
            'expires_at' => ($data['expires_at'] ?? null) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'notes' => ($data['notes'] ?? null) ?: null,
        ];
    }
}