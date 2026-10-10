<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Toko',
            'email' => 'admin@kasirku.test',
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Kasir',
            'email' => 'kasir@kasirku.test',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $categories = collect(['Makanan', 'Minuman', 'Sembako', 'Kebersihan'])
            ->map(fn (string $name) => Category::create(['name' => $name]));

        $products = [
            ['barcode' => '8991002100011', 'name' => 'Indomie Goreng', 'cost_price' => 2800, 'selling_price' => 3500, 'stock' => 100, 'unit' => 'pcs'],
            ['barcode' => '8991002100028', 'name' => 'Aqua 600ml', 'cost_price' => 2500, 'selling_price' => 4000, 'stock' => 80, 'unit' => 'botol'],
            ['barcode' => '8991002100035', 'name' => 'Teh Pucuk 350ml', 'cost_price' => 3000, 'selling_price' => 4500, 'stock' => 60, 'unit' => 'botol'],
            ['barcode' => '8991002100042', 'name' => 'Beras Premium 5kg', 'cost_price' => 62000, 'selling_price' => 72000, 'stock' => 20, 'unit' => 'sak'],
            ['barcode' => '8991002100059', 'name' => 'Minyak Goreng 1L', 'cost_price' => 15500, 'selling_price' => 18000, 'stock' => 40, 'unit' => 'botol'],
            ['barcode' => '8991002100066', 'name' => 'Sabun Mandi Lifebuoy', 'cost_price' => 3800, 'selling_price' => 5000, 'stock' => 8, 'unit' => 'pcs'],
            ['barcode' => '8991002100073', 'name' => 'Gula Pasir 1kg', 'cost_price' => 14000, 'selling_price' => 16500, 'stock' => 30, 'unit' => 'pack'],
            ['barcode' => '8991002100080', 'name' => 'Kopi Kapal Api', 'cost_price' => 1500, 'selling_price' => 2000, 'stock' => 4, 'unit' => 'pcs'],
        ];

        foreach ($products as $index => $data) {
            $product = Product::create($data + [
                'category_id' => $categories[$index % $categories->count()]->id,
                'min_stock' => 5,
            ]);

            StockMovement::record($product, 'in', $product->stock, 'Stok awal', $admin->id);
        }
    }
}
