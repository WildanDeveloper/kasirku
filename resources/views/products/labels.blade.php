<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label Harga — {{ $shop['name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Figtree', system-ui, sans-serif; }
    </style>
</head>
<body class="label-print bg-white" x-data>
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-4 print:hidden">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Label Harga ({{ $products->count() }} produk)</h1>
                <p class="text-sm text-gray-500">{{ $mode === 'wholesale' ? 'Harga Grosir' : 'Harga Ecer' }} · {{ $shop['name'] }}</p>
            </div>
            <button @click="window.print()" class="btn-primary">Cetak</button>
        </div>

        <div id="label-sheet" class="label-sheet">
            @foreach ($products as $product)
                <div class="label-card">
                    <div class="text-xs font-semibold text-gray-500 truncate">{{ $shop['name'] }}</div>
                    <div class="text-[13px] font-bold leading-tight mt-0.5 line-clamp-2">{{ $product->name }}</div>
                    <div class="text-lg font-extrabold text-gray-900 mt-1">
                        Rp {{ number_format($mode === 'wholesale' ? $product->wholesalePrice() : $product->selling_price, 0, ',', '.') }}
                    </div>
                    @if ($product->barcode)
                        <div class="text-[10px] text-gray-500 mt-0.5">{{ $product->barcode }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        window.addEventListener('load', () => {
            if (new URLSearchParams(location.search).get('print') === '1') {
                setTimeout(() => window.print(), 300);
            }
        });
    </script>
</body>
</html>