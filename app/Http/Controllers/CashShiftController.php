<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use Illuminate\Http\Request;

class CashShiftController extends Controller
{
    public function index(Request $request)
    {
        $openShift = CashShift::open()->where('user_id', $request->user()->id)->latest()->first();

        $shifts = CashShift::with('user')
            ->when(! $request->user()->is_admin, fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('opened_at')
            ->paginate(15);

        return view('shifts.index', compact('openShift', 'shifts'));
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_balance' => ['required', 'integer', 'min:0'],
        ]);

        $existing = CashShift::open()->where('user_id', $request->user()->id)->first();
        abort_if($existing, 422, 'Masih ada shift yang terbuka.');

        CashShift::create([
            'user_id' => $request->user()->id,
            'opening_balance' => $request->opening_balance,
            'opened_at' => now(),
        ]);

        return back()->with('success', 'Shift dibuka.');
    }

    public function close(Request $request, CashShift $shift)
    {
        abort_unless($shift->user_id === $request->user()->id || $request->user()->is_admin, 403);
        abort_if($shift->closed_at, 422, 'Shift sudah ditutup.');

        $data = $request->validate([
            'closing_balance' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $shift->update([
            'expected_balance' => $shift->expectedCash(),
            'closing_balance' => $data['closing_balance'],
            'closed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        $diff = $data['closing_balance'] - $shift->expected_balance;

        return back()->with(
            $diff === 0 ? 'success' : 'warning',
            'Shift ditutup. Selisih kas: Rp '.number_format($diff, 0, ',', '.').'.'
        );
    }
}
