<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->date('month') ?: now()->startOfMonth();

        $expenses = Expense::whereBetween('spent_at', [$month->copy()->startOfDay(), $month->copy()->addMonth()->subDay()->endOfDay()])
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $total = Expense::whereBetween('spent_at', [$month->copy()->startOfDay(), $month->copy()->addMonth()->subDay()->endOfDay()])
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'total', 'month'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:listrik,air,sewa,gaji,internet,transportasi,lainnya'],
            'amount' => ['required', 'integer', 'min:1'],
            'spent_at' => ['required', 'date'],
        ]);

        Expense::create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Pengeluaran dicatat.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Pengeluaran dihapus.');
    }
}
