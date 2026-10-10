<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\LoyaltyTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CustomerWalletController extends Controller
{
    /** Tambah kasbon (pelanggan bayar di muka). */
    public function storeDeposit(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        CustomerDeposit::create([
            'customer_id' => $customer->id,
            'type' => 'in',
            'amount' => $data['amount'],
            'note' => $data['note'] ?? 'Setoran kasbon',
            'user_id' => $request->user()->id,
        ]);

        ActivityLogger::record('deposit.in', 'Kasbon '.$customer->name.' +Rp '.number_format($data['amount'], 0, ',', '.'), $customer);

        return back()->with('success', 'Kasbon Rp '.number_format($data['amount'], 0, ',', '.').' ditambahkan.');
    }

    /** Kurangi kasbon manual (mis. retur kasbon). */
    public function storeWithdrawal(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $balance = $customer->depositBalance();
        abort_if($balance < $data['amount'], 422, 'Saldo kasbon tidak cukup (tersedia Rp '.number_format($balance, 0, ',', '.').').');

        CustomerDeposit::create([
            'customer_id' => $customer->id,
            'type' => 'out',
            'amount' => $data['amount'],
            'note' => $data['note'] ?? 'Pengurangan kasbon',
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Kasbon dikurangi Rp '.number_format($data['amount'], 0, ',', '.').'.');
    }

    /** Tambah/kurangi poin manual (korreksi admin). */
    public function storePoints(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        abort_if($data['points'] == 0, 422, 'Jumlah poin tidak boleh nol.');
        abort_if($data['points'] < 0 && $customer->points < abs($data['points']), 422, 'Poin pelanggan tidak cukup.');

        LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'type' => 'adjust',
            'points' => $data['points'],
            'note' => $data['note'] ?? 'Penyesuaian admin',
            'user_id' => $request->user()->id,
        ]);

        $customer->update(['points' => $customer->points + $data['points']]);

        ActivityLogger::record('loyalty.adjust', 'Poin '.$customer->name.' '.($data['points'] > 0 ? '+' : '').$data['points'], $customer);

        return back()->with('success', 'Poin diperbarui.');
    }
}