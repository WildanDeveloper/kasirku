<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pengaturan Toko</h2>
            <p class="text-sm text-gray-500">Identitas toko & struk.</p>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="page-card p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="store_name" value="Nama Toko" />
                <x-text-input id="store_name" name="store_name" value="{{ old('store_name', \App\Models\Setting::get('store_name')) }}" required class="mt-1.5 form-input" />
            </div>

            <div>
                <x-input-label for="store_address" value="Alamat" />
                <x-text-input id="store_address" name="store_address" value="{{ old('store_address', \App\Models\Setting::get('store_address')) }}" class="mt-1.5 form-input" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="store_phone" value="Telepon" />
                    <x-text-input id="store_phone" name="store_phone" value="{{ old('store_phone', \App\Models\Setting::get('store_phone')) }}" class="mt-1.5 form-input" />
                </div>
                <div>
                    <x-input-label for="tax_percent" value="Pajak / PPN (%)" />
                    <x-text-input id="tax_percent" name="tax_percent" type="number" step="0.1" min="0" max="100" value="{{ old('tax_percent', \App\Models\Setting::get('tax_percent')) }}" class="mt-1.5 form-input" />
                </div>
            </div>

            <div>
                <x-input-label for="receipt_footer" value="Catatan Kaki Struk" />
                <x-text-input id="receipt_footer" name="receipt_footer" value="{{ old('receipt_footer', \App\Models\Setting::get('receipt_footer')) }}" class="mt-1.5 form-input" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                <div>
                    <x-input-label for="printer_width" value="Lebar Struk" />
                    <select id="printer_width" name="printer_width" class="form-input mt-1">
                        <option value="58" @selected(\App\Models\Setting::get('printer_width') === '58')>Printer thermal 58 mm</option>
                        <option value="80" @selected(\App\Models\Setting::get('printer_width') !== '58')>Printer thermal 80 mm</option>
                        <option value="a4" @selected(\App\Models\Setting::get('printer_width') === 'a4')>Printer kertas A4</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="printer_auto_print" value="0">
                        <input type="checkbox" name="printer_auto_print" value="1"
                               @checked(\App\Models\Setting::get('printer_auto_print') === '1')
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        Cetak struk otomatis
                    </label>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('settings.backup.index') }}" class="btn-secondary w-full">Backup Database</a>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100">
                <button class="btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</x-app-layout>
