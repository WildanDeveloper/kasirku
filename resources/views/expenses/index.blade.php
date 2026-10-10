<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pengeluaran</h2>
            <p class="text-sm text-gray-500">Biaya operasional toko — memengaruhi laba bersih.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 space-y-6">
                <x-stat-card label="Total Pengeluaran {{ $month->translatedFormat('F Y') }}" value="Rp {{ number_format($total, 0, ',', '.') }}" tone="red" icon="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />

                <div class="page-card overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <form method="GET" class="flex gap-2 items-center">
                            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-input sm:w-44">
                            <button class="btn-secondary">Filter</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="table-th">Tanggal</th>
                                    <th class="table-th">Keterangan</th>
                                    <th class="table-th">Kategori</th>
                                    <th class="table-th text-right">Jumlah</th>
                                    <th class="table-th"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($expenses as $expense)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="table-td whitespace-nowrap text-gray-500">{{ $expense->spent_at->format('d/m/Y') }}</td>
                                        <td class="table-td font-semibold text-gray-900">{{ $expense->description }}</td>
                                        <td class="table-td">
                                            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">{{ ucfirst($expense->category) }}</span>
                                        </td>
                                        <td class="table-td text-right font-bold text-red-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                        <td class="table-td text-right">
                                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus pengeluaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-sm font-semibold text-red-500 hover:text-red-700">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-10 text-center text-sm text-gray-500">Tidak ada pengeluaran bulan ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($expenses->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $expenses->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="page-card p-5">
                <h3 class="section-title mb-4">Catat Pengeluaran</h3>
                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="description" required placeholder="Keterangan (mis. Bayar listrik)" class="form-input">
                    <select name="category" class="form-input">
                        @foreach (['listrik', 'air', 'sewa', 'gaji', 'internet', 'transportasi', 'lainnya'] as $cat)
                            <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="amount" min="1" required placeholder="Jumlah (Rp)" class="form-input">
                    <input type="date" name="spent_at" value="{{ now()->format('Y-m-d') }}" required class="form-input">
                    <button class="w-full btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
