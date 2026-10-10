@php $editing = isset($product) && $product->exists; @endphp

<div>
    <x-input-label for="barcode" value="Barcode" />
    <x-text-input id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="mt-1.5 form-input" placeholder="Scan atau ketik barcode" />
    <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
</div>

<div>
    <x-input-label for="name" value="Nama Produk" />
    <x-text-input id="name" name="name" value="{{ old('name', $product->name) }}" required class="mt-1.5 form-input" placeholder="Contoh: Indomie Goreng" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="category_id" value="Kategori" />
    <select id="category_id" name="category_id" class="mt-1.5 form-input">
        <option value="">- Tanpa kategori -</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="cost_price" value="Harga Beli (Rp)" />
        <x-text-input id="cost_price" name="cost_price" type="number" min="0" value="{{ old('cost_price', $product->cost_price) }}" required class="mt-1.5 form-input" />
        <x-input-error :messages="$errors->get('cost_price')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="selling_price" value="Harga Jual (Rp)" />
        <x-text-input id="selling_price" name="selling_price" type="number" min="0" value="{{ old('selling_price', $product->selling_price) }}" required class="mt-1.5 form-input" />
        <x-input-error :messages="$errors->get('selling_price')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4">
    <div>
        <x-input-label for="stock" value="Stok" />
        <x-text-input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $product->stock) }}" required class="mt-1.5 form-input" />
        <x-input-error :messages="$errors->get('stock')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="min_stock" value="Stok Minimum" />
        <x-text-input id="min_stock" name="min_stock" type="number" min="0" value="{{ old('min_stock', $product->min_stock) }}" required class="mt-1.5 form-input" />
        <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="unit" value="Satuan" />
        <x-text-input id="unit" name="unit" value="{{ old('unit', $product->unit) }}" required class="mt-1.5 form-input" placeholder="pcs" />
        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
    </div>
</div>

@if ($editing)
    <p class="flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2.5 text-xs text-blue-700">
        <svg class="h-4 w-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        Ubah stok lewat menu <strong class="mx-1">Stok</strong> agar tercatat riwayat pergerakannya.
    </p>
@endif
