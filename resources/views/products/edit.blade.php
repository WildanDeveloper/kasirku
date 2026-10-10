<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Edit Produk</h2>
            <p class="text-sm text-gray-500">{{ $product->name }}</p>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('products.update', $product) }}" class="page-card p-6 space-y-4">
            @csrf
            @method('PUT')

            @include('products._form')

            <div class="flex gap-2 pt-2 border-t border-gray-100">
                <button class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
