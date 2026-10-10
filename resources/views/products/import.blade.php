<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Import Produk Massal</h2>
            <p class="text-sm text-gray-500">Tempel data dari Excel/Google Sheets atau file CSV.</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="page-card p-6 space-y-5">
            <div class="rounded-lg bg-blue-50 border border-blue-100 p-4 text-sm text-blue-800">
                <p class="font-semibold mb-2">Format per baris (pemisah tab, `;`, atau koma):</p>
                <code class="block rounded bg-white/70 px-3 py-2 text-xs font-mono overflow-x-auto">
barcode; nama; kategori; harga_beli; harga_jual; stok; satuan; stok_min
                </code>
                <p class="mt-2 text-xs">Hanya <strong>nama</strong> wajib diisi. Kolom lain boleh kosong (stok min default 5, satuan default pcs). Barcode yang sudah ada → data produk diperbarui. Kategori dicocokkan dengan nama kategori yang sudah ada; kalau belum ada, kosongkan atau buat dulu di menu produk.</p>
            </div>

            <form method="POST" action="{{ route('products.import') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="source" value="Data produk" />
                    <textarea id="source" name="source" rows="10" required
                              class="mt-1.5 form-input font-mono text-xs"
                              placeholder="8991002100011; Indomie Goreng; Makanan; 2800; 3500; 100; pcs; 10&#10;8991002100028; Aqua 600ml; Minuman; 2500; 4000; 80; botol; 12"></textarea>
                    <x-input-error :messages="$errors->get('source')" class="mt-2" />
                </div>

                <div class="flex gap-2 pt-2 border-t border-gray-100">
                    <button class="btn-primary">Import Sekarang</button>
                    <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
