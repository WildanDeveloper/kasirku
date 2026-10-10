<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\LoyaltyTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test fitur operasional: harga grosir bertingkat, poin loyalitas, kasbon,
 * pembelian & supplier, stok opname, X/Z report, dan notifikasi.
 */
class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Toko',
            'email' => 'admin@kasirku.test',
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->kasir = User::factory()->create([
            'name' => 'Kasir Satu',
            'email' => 'kasir@kasirku.test',
            'is_admin' => false,
            'is_active' => true,
        ]);

        CashShift::create([
            'user_id' => $this->kasir->id,
            'opening_balance' => 100000,
            'opened_at' => now()->subHour(),
        ]);
    }

    protected function product(array $attributes = []): Product
    {
        $categoryId = Category::firstOrCreate(['name' => 'Sembako'])->id;

        return Product::create(array_merge([
            'barcode' => 'BC'.random_int(1000, 9999),
            'name' => 'Produk Uji',
            'category_id' => $categoryId,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'stock' => 50,
            'min_stock' => 5,
            'unit' => 'pcs',
        ], $attributes));
    }

    protected function checkout(array $data): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->kasir)->postJson(route('pos.checkout'), $data);
    }

    // ------------------------------------------------------------------
    // Harga grosir bertingkat
    // ------------------------------------------------------------------

    public function test_harga_bertingkat_dipakai_berdasarkan_jumlah_pembelian(): void
    {
        $product = $this->product(['price_wholesale' => 14000]);
        $product->priceTiers()->createMany([
            ['min_qty' => 10, 'price' => 13000],
            ['min_qty' => 20, 'price' => 12000],
        ]);

        $this->assertSame(15000, $product->finalPriceFor(null, 1), 'Pakai harga ecer');
        $this->assertSame(15000, $product->finalPriceFor(null, 5), 'Di bawah tier pertama, tetap harga ecer');
        $this->assertSame(13000, $product->finalPriceFor(null, 10), 'Tier 10 kepakai');
        $this->assertSame(12000, $product->finalPriceFor(null, 25), 'Tier tertinggi kepakai');
    }

    public function test_harga_khusus_pelanggan_menang_dari_harga_bertingkat(): void
    {
        $customer = Customer::create(['name' => 'Bu Tuti', 'is_wholesale' => true]);
        $product = $this->product(['price_wholesale' => 14000]);
        $product->priceTiers()->create(['min_qty' => 10, 'price' => 12000]);
        $product->customerPrices()->create(['customer_id' => $customer->id, 'price' => 11000]);

        $this->assertSame(11000, $product->fresh()->finalPriceFor($customer, 25));
    }

    public function test_tier_bisa_disimpan_lewat_form_produk(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'barcode' => 'TIER001',
            'name' => 'Kopi Bubuk',
            'cost_price' => 20000,
            'selling_price' => 28000,
            'stock' => 100,
            'min_stock' => 10,
            'unit' => 'pcs',
            'tiers' => [
                ['min_qty' => 6, 'price' => 27000],
                ['min_qty' => 12, 'price' => 26000],
                ['min_qty' => 6, 'price' => 25000], // duplikat, harus diabaikan
                ['min_qty' => 0, 'price' => 9999],   // tidak valid, dibuang
            ],
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', 'TIER001')->firstOrFail();

        $this->assertSame(2, $product->priceTiers()->count(), 'Tier duplikat/tidak valid dibuang');
        $this->assertSame(27000, $product->tierPrice(6));
        $this->assertSame(26000, $product->tierPrice(12));
    }

    public function test_harga_tier_dipakai_otomatis_di_kasir(): void
    {
        $product = $this->product(['price_wholesale' => null, 'selling_price' => 15000]);
        $product->priceTiers()->create(['min_qty' => 10, 'price' => 12000]);

        $this->checkout([
            'items' => [['product_id' => $product->id, 'quantity' => 12]],
            'payment_method' => 'cash',
            'paid_amount' => 144000,
        ])->assertOk();

        $this->assertSame(144000, SaleItem::query()->first()->subtotal);
    }

    // ------------------------------------------------------------------
    // Poin loyalitas
    // ------------------------------------------------------------------

    public function test_transaksi_menghasilkan_poin_loyalitas(): void
    {
        Setting::put('loyalty_percent', '1');
        Setting::put('loyalty_point_value', '100');

        $customer = Customer::create(['name' => 'Bu Sari']);
        $product = $this->product();

        $this->checkout([
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
            'payment_method' => 'cash',
            'paid_amount' => 60000,
        ])->assertOk();

        $this->assertSame(600, $customer->fresh()->points, '1% dari Rp 60.000 = 600 poin');
        $this->assertDatabaseHas('loyalty_transactions', ['customer_id' => $customer->id, 'type' => 'earn', 'points' => 600]);
    }

    public function test_poin_tidak_diberikan_kepada_transaksi_tanpa_pelanggan(): void
    {
        Setting::put('loyalty_percent', '5');

        $product = $this->product();

        $this->checkout([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 30000,
        ])->assertOk();

        $this->assertSame(0, LoyaltyTransaction::count());
    }

    public function test_poin_dapat_ditukar_sebagai_potongan_harga(): void
    {
        Setting::put('loyalty_percent', '0');
        Setting::put('loyalty_point_value', '100');
        Setting::put('loyalty_max_redeem_percent', '100');

        $customer = Customer::create(['name' => 'Pak Budi', 'points' => 1000]);
        $product = $this->product();

        $response = $this->checkout([
            'customer_id' => $customer->id,
            'redeem_points' => 200,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 30000,
        ])->assertOk();

        // Total 30.000 (2 x 15.000) - (200 poin x Rp 100) = 10.000
        $this->assertSame(10000, $response->json('total'));
        $this->assertSame(200, $response->json('points_redeemed'));
        $this->assertSame(800, $customer->fresh()->points);
        $this->assertDatabaseHas('loyalty_transactions', ['customer_id' => $customer->id, 'type' => 'redeem', 'points' => -200]);
    }

    public function test_poin_ditolak_melebihi_saldo_pelanggan(): void
    {
        Setting::put('loyalty_max_redeem_percent', '100');
        $customer = Customer::create(['name' => 'Pak Rudi', 'points' => 50]);
        $product = $this->product();

        $this->checkout([
            'customer_id' => $customer->id,
            'redeem_points' => 500,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'paid_amount' => 15000,
        ])->assertStatus(422);
    }

    public function test_penukaran_poin_dibatasi_presentase_maksimum(): void
    {
        Setting::put('loyalty_point_value', '100');
        Setting::put('loyalty_max_redeem_percent', '10');

        $customer = Customer::create(['name' => 'Bu Maya', 'points' => 50000]);
        $product = $this->product();

        $response = $this->checkout([
            'customer_id' => $customer->id,
            'redeem_points' => 50000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'paid_amount' => 14500,
        ])->assertOk();

        // Maksimal 10% dari 15.000 = 1.500 (15 poin)
        $this->assertSame(13500, $response->json('total'));
        $this->assertSame(49985, $customer->fresh()->points);
    }

    public function test_admin_bisa_menyesuaikan_poin_pelanggan(): void
    {
        $customer = Customer::create(['name' => 'Bu Tika']);

        $this->actingAs($this->admin)
            ->post(route('customers.points.store', $customer), ['points' => 250])
            ->assertRedirect();

        $this->assertSame(250, $customer->fresh()->points);

        $this->actingAs($this->admin)
            ->post(route('customers.points.store', $customer), ['points' => -1000])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Kasbon pelanggan
    // ------------------------------------------------------------------

    public function test_kasbon_dapat_ditambah_dan_dipakai_saat_belanja(): void
    {
        Setting::put('loyalty_percent', '0');

        $customer = Customer::create(['name' => 'Pak Hendra']);
        $product = $this->product();

        $this->actingAs($this->admin)
            ->post(route('customers.deposits.store', $customer), ['amount' => 20000])
            ->assertRedirect();

        $this->assertSame(20000, $customer->depositBalance());

        $response = $this->checkout([
            'customer_id' => $customer->id,
            'use_deposit' => 12000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 18000,
        ])->assertOk();

        $this->assertSame(30000, $response->json('total'));
        $this->assertSame(12000, $response->json('deposit_used'));
        $this->assertSame(8000, $customer->depositBalance(), 'Sisa kasbon berkurang');

        $sale = Sale::latest('id')->first();
        $this->assertSame(30000, $sale->paid_amount, 'Kasbon dihitung sebagai bagian dari pembayaran');
    }

    public function test_kasbon_tidak_boleh_dipakai_lebih_dari_saldo(): void
    {
        Setting::put('loyalty_percent', '0');

        $customer = Customer::create(['name' => 'Pak Joko']);
        $product = $this->product();

        $this->actingAs($this->admin)
            ->post(route('customers.deposits.store', $customer), ['amount' => 5000]);

        $this->checkout([
            'customer_id' => $customer->id,
            'use_deposit' => 900000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 30000,
        ])->assertOk();

        $this->assertSame(0, $customer->depositBalance(), 'Hanya saldo yang terpakai');
    }

    public function test_pengurangan_kasbon_ditolak_bila_saldo_kurang(): void
    {
        $customer = Customer::create(['name' => 'Bu Ratna']);

        $this->actingAs($this->admin)
            ->post(route('customers.deposits.withdraw', $customer), ['amount' => 1000])
            ->assertStatus(422);
    }

    public function test_kasbon_tidak_boleh_dipakai_tanpa_pelanggan(): void
    {
        $product = $this->product();

        $this->checkout([
            'use_deposit' => 5000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'paid_amount' => 15000,
        ])->assertOk();

        $this->assertSame(0, CustomerDeposit::count());
    }

    // ------------------------------------------------------------------
    // Pembelian & supplier
    // ------------------------------------------------------------------

    public function test_pembelian_menambah_stok_dan_memperbarui_harga_beli(): void
    {
        $supplier = Supplier::create(['name' => 'PT Sumber Rejeki']);
        $product = $this->product(['stock' => 5, 'cost_price' => 9000]);

        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'discount' => 5000,
            'paid_amount' => 100000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'cost_price' => 12000],
            ],
        ])->assertRedirect(route('purchases.index'));

        $product->refresh();

        $this->assertSame(15, $product->stock, 'Stok 5 + 10');
        $this->assertSame(12000, $product->cost_price);

        $purchase = Purchase::latest('id')->first();
        $this->assertSame(115000, $purchase->total, '120.000 - diskon 5.000');
        $this->assertSame(15000, $purchase->outstanding());
    }

    public function test_pembelian_menyimpan_harga_beli_saat_transaksi(): void
    {
        $product = $this->product(['cost_price' => 10000]);
        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-1',
            'user_id' => $this->kasir->id,
            'subtotal' => 15000,
            'total' => 15000,
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 15000,
            'cost_at_sale' => 10000,
            'subtotal' => 15000,
        ]);

        // Harga beli berubah setelah penjualan; laporan tetap pakai harga saat jual.
        $product->update(['cost_price' => 30000]);

        $this->assertSame(10000, SaleItem::query()->first()->costPrice());
    }

    public function test_pembayaran_supplier_mengurangi_utang(): void
    {
        $supplier = Supplier::create(['name' => 'CV Maju']);
        $product = $this->product(['stock' => 0]);

        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'cost_price' => 8000]],
        ]);

        $purchase = Purchase::latest('id')->first();
        $this->assertSame(80000, $purchase->outstanding());

        $this->actingAs($this->admin)->post(route('purchases.pay', $purchase), [
            'amount' => 30000,
            'payment_method' => 'transfer',
        ])->assertRedirect();

        $purchase->refresh();
        $this->assertSame(50000, $purchase->outstanding());
        $this->assertFalse($purchase->isPaid());

        $this->actingAs($this->admin)->post(route('purchases.pay', $purchase), [
            'amount' => 50000,
            'payment_method' => 'cash',
        ]);

        $this->assertTrue($purchase->fresh()->isPaid());
    }

    public function test_pembelian_yang_sudah_dibayar_tidak_bisa_dibatalkan(): void
    {
        $supplier = Supplier::create(['name' => 'UD Berkah']);
        $product = $this->product(['stock' => 0]);

        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'paid_amount' => 50000,
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'cost_price' => 8000]],
        ]);

        $purchase = Purchase::latest('id')->first();

        $this->actingAs($this->admin)->post(route('purchases.cancel', $purchase))->assertStatus(422);
    }

    public function test_pembatalan_pembelian_mengembalikan_stok(): void
    {
        $supplier = Supplier::create(['name' => 'Toko Serba Ada']);
        $product = $this->product(['stock' => 5]);

        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'cost_price' => 8000]],
        ]);

        $this->assertSame(15, $product->fresh()->stock);

        $purchase = Purchase::latest('id')->first();
        $this->actingAs($this->admin)->post(route('purchases.cancel', $purchase))->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $purchase->fresh()->status);
    }

    public function test_pembelian_tanpa_barang_ditolak(): void
    {
        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'purchase_date' => today()->toDateString(),
            'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_supplier_berriwayat_tidak_bisa_dihapus(): void
    {
        $supplier = Supplier::create(['name' => 'PT Abadi']);
        $product = $this->product(['stock' => 0]);

        $this->actingAs($this->admin)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'cost_price' => 5000]],
        ]);

        $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier))->assertStatus(422);
    }

    public function test_supplier_tanpa_riwayat_bisa_dihapus(): void
    {
        $supplier = Supplier::create(['name' => 'PT Sementara']);

        $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier))->assertRedirect();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    // ------------------------------------------------------------------
    // Stok opname
    // ------------------------------------------------------------------

    public function test_opname_menyesuaikan_stok_saat_diselesaikan(): void
    {
        $product = $this->product(['stock' => 20]);

        $this->actingAs($this->admin)->post(route('opnames.store'), [
            'name' => 'Opname Bulanan',
        ])->assertRedirect();

        $opname = StockOpname::latest('id')->first();
        $item = $opname->items()->first();
        $this->assertSame(20, $item->system_qty);

        $this->actingAs($this->admin)->post(route('opnames.items.update', $opname), [
            'items' => [$item->id => 17],
        ])->assertRedirect();

        // Belum selesai, stok belum berubah.
        $this->assertSame(20, $product->fresh()->stock);

        $this->actingAs($this->admin)->post(route('opnames.finish', $opname))->assertRedirect(route('opnames.show', $opname));

        $opname->refresh();
        $this->assertSame('finished', $opname->status);
        $this->assertSame(17, $product->fresh()->stock);
        $this->assertSame(-30000, $opname->difference_value, 'Selisih -3 x HPP 10.000');
        $this->assertSame(-3, $opname->items()->first()->difference);
    }

    public function test_opname_tidak_bisa_diselesaikan_saat_masih_ada_yang_belum_dihitung(): void
    {
        $this->product(['stock' => 10]);
        $this->product(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('opnames.store'), ['name' => 'Opname Setengah']);
        $opname = StockOpname::latest('id')->first();

        $this->actingAs($this->admin)->post(route('opnames.finish', $opname));

        $this->assertSame('draft', $opname->fresh()->status);
    }

    public function test_opname_yang_sudah_selesai_tidak_bisa_diubah(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('opnames.store'), ['name' => 'Opname Selesai']);
        $opname = StockOpname::latest('id')->first();
        $item = $opname->items()->first();

        $this->actingAs($this->admin)->post(route('opnames.items.update', $opname), ['items' => [$item->id => 8]]);
        $this->actingAs($this->admin)->post(route('opnames.finish', $opname));

        $this->actingAs($this->admin)->post(route('opnames.items.update', $opname), ['items' => [$item->id => 2]])
            ->assertStatus(422);

        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_opname_mencatat_penyesuaian_sebagai_pergerakan_stok(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('opnames.store'), ['name' => 'Opname Riwayat']);
        $opname = StockOpname::latest('id')->first();

        $this->actingAs($this->admin)->post(route('opnames.items.update', $opname), ['items' => [$opname->items()->first()->id => 13]]);
        $this->actingAs($this->admin)->post(route('opnames.finish', $opname));

        $movement = StockMovement::where('type', 'adjustment')->latest('id')->first();

        $this->assertSame(13, $movement->stock_after);
        $this->assertSame(10, $movement->stock_before);
        $this->assertSame(3, $movement->quantity, 'Selisih yang dicatat');
    }

    public function test_opname_boleh_dibatalkan_selama_berjalan(): void
    {
        $this->product(['stock' => 5]);

        $this->actingAs($this->admin)->post(route('opnames.store'), ['name' => 'Opname Batal']);
        $opname = StockOpname::latest('id')->first();

        $this->actingAs($this->admin)->delete(route('opnames.destroy', $opname))->assertRedirect();

        $this->assertDatabaseMissing('stock_opnames', ['id' => $opname->id]);
    }

    // ------------------------------------------------------------------
    // X-Report & Z-Report
    // ------------------------------------------------------------------

    public function test_x_dan_z_report_tampil_dengan_rekap_per_kasir(): void
    {
        $product = $this->product();

        $this->checkout([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'paid_amount' => 30000,
        ])->assertOk();

        $this->actingAs($this->kasir)->get(route('reports.x'))->assertOk()->assertSee('X-Report');
        $this->actingAs($this->kasir)->get(route('reports.z'))->assertOk()->assertSee('Z-Report');
        $this->actingAs($this->kasir)->get(route('reports.x', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('Kasir Satu');
    }

    // ------------------------------------------------------------------
    // Notifikasi
    // ------------------------------------------------------------------

    public function test_pemeriksa_memberi_notifikasi_stok_menipis(): void
    {
        Setting::put('low_stock_alert', '1');
        $this->product(['name' => 'Tepung Terigu', 'stock' => 2, 'min_stock' => 5]);

        $this->artisan('kasirku:alerts')->assertSuccessful();

        $this->assertDatabaseHas('app_notifications', ['type' => 'low_stock_summary']);
    }

    public function test_notifikasi_stok_menipis_bisa_dimatikan(): void
    {
        Setting::put('low_stock_alert', '0');
        $this->product(['stock' => 1, 'min_stock' => 5]);

        $this->artisan('kasirku:alerts')->assertSuccessful();

        $this->assertSame(0, AppNotification::where('type', 'like', 'low_stock%')->count());
    }

    public function test_notifikasi_berganda_dicegah(): void
    {
        Setting::put('low_stock_alert', '1');
        $this->product(['stock' => 1, 'min_stock' => 5]);

        $this->artisan('kasirku:alerts')->assertSuccessful();
        $first = AppNotification::count();

        $this->artisan('kasirku:alerts')->assertSuccessful();

        $this->assertSame($first, AppNotification::count(), 'Pesan sama tidak diulang dalam 6 jam');
    }

    public function test_shift_yang_menggantung_muncul_di_notifikasi(): void
    {
        CashShift::whereNull('closed_at')->update(['opened_at' => now()->subDays(2)]);

        $this->artisan('kasirku:alerts')->assertSuccessful();

        $this->assertDatabaseHas('app_notifications', ['type' => 'shift_open']);
    }

    public function test_notifikasi_bisa_ditandai_dibaca(): void
    {
        AppNotification::create(['type' => 'info', 'title' => 'Tes', 'message' => 'Halo']);

        $this->actingAs($this->admin)->post(route('notifications.read_all'))->assertRedirect();

        $this->assertNotNull(AppNotification::first()->read_at);
    }

    // ------------------------------------------------------------------
    // Halaman baru harus bisa dibuka
    // ------------------------------------------------------------------

    public function test_semua_halaman_baru_dapat_diakses(): void
    {
        $this->actingAs($this->admin);

        foreach (['purchases.index', 'purchases.create', 'suppliers.index', 'opnames.index',
            'notifications.index', 'reports.x', 'reports.z'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}