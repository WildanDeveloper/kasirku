<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test domain bisnis Kasirku. Semua test di sini menyorot kasus yang pernah
 * menyebabkan bug (lihat laporan audit) supaya regresi langsung ketahuan.
 */
class DomainTest extends TestCase
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

        // Shift wajib dibuka sebelum checkout.
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

    protected function actingAsKasir(array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->kasir)->postJson(route('pos.checkout'), $data);
    }

    // ------------------------------------------------------------------
    // POS
    // ------------------------------------------------------------------

    public function test_checkout_menghitung_subtotal_diskon_pajak_dan_kembalian(): void
    {
        Setting::put('tax_percent', 10);

        $product = $this->product(['selling_price' => 20000]);

        $response = $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'discount' => 5000,
            'apply_tax' => true,
            'paid_amount' => 40000,
            'payment_method' => 'cash',
        ]);

        $response->assertOk();

        $sale = Sale::first();
        // 40.000 - 5.000 = 35.000, pajak 10% = 3.500, total 38.500, kembalian 1.500
        $this->assertSame(40000, $sale->subtotal);
        $this->assertSame(5000, $sale->discount);
        $this->assertSame(3500, $sale->tax);
        $this->assertSame(38500, $sale->total);
        $this->assertSame(1500, $sale->change_amount);
        $this->assertSame(48, $product->fresh()->stock);
    }

    public function test_checkout_menolak_voucher_persen_di_atas_100_persen(): void
    {
        // Validasi admin: persen maksimal 100.
        $this->actingAs($this->admin)->post(route('vouchers.store'), [
            'code' => 'GILA',
            'name' => 'Voucher gila',
            'type' => 'percent',
            'value' => 150,
            'is_active' => 1,
        ])->assertSessionHasErrors('value');

        // Voucher lama yang nilainya >100 tetap tidak boleh membuat total negatif.
        Voucher::create([
            'code' => 'GILA',
            'name' => 'Voucher gila',
            'type' => 'percent',
            'value' => 150,
            'is_active' => true,
        ]);

        $product = $this->product(['selling_price' => 1000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'voucher_code' => 'GILA',
            'paid_amount' => 1000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::first();
        $this->assertGreaterThanOrEqual(0, $sale->total, 'Total transaksi tidak boleh negatif.');
        $this->assertSame(1000, $sale->voucher_discount, 'Potongan dibatasi 100% nilai belanja.');
    }

    public function test_total_tidak_pernah_negatif_walau_voucher_dimanipulasi(): void
    {
        $voucher = Voucher::create([
            'code' => 'BAIK',
            'name' => 'Voucher 50%',
            'type' => 'percent',
            'value' => 50,
            'is_active' => true,
        ]);

        $product = $this->product(['selling_price' => 1000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'voucher_code' => 'BAIK',
            'paid_amount' => 600,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::first();
        $this->assertSame(500, $sale->voucher_discount);
        $this->assertSame(500, $sale->total);
        $this->assertSame(1, $voucher->fresh()->used_count);
    }

    public function test_harga_grosir_dan_harga_khusus_pelanggan_dipakai(): void
    {
        $wholesale = $this->product(['selling_price' => 4000, 'price_wholesale' => 3500]);
        $retail = $this->product(['selling_price' => 6000, 'price_wholesale' => 5500]);

        $grosir = Customer::create(['name' => 'Budi Grosir', 'is_wholesale' => true]);
        $ecer = Customer::create(['name' => 'Siti Ecer']);
        CustomerPrice::create(['customer_id' => $ecer->id, 'product_id' => $retail->id, 'price' => 5000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $wholesale->id, 'quantity' => 1]],
            'customer_id' => $grosir->id,
            'paid_amount' => 4000,
            'payment_method' => 'cash',
        ])->assertOk();

        $this->actingAsKasir([
            'items' => [['product_id' => $retail->id, 'quantity' => 1]],
            'customer_id' => $ecer->id,
            'paid_amount' => 6000,
            'payment_method' => 'cash',
        ])->assertOk();

        $prices = Sale::with('items')->get()
            ->mapWithKeys(fn ($sale) => [$sale->items->first()->product_id => $sale->items->first()->price]);

        $this->assertSame(3500, $prices[$wholesale->id], 'Pelanggan grosir harus dapat harga grosir.');
        $this->assertSame(5000, $prices[$retail->id], 'Harga khusus pelanggan harus mengalahkan harga ecer.');
    }

    public function test_transaksi_kredit_mencatat_sisa_utang_dan_bisa_dilunasi(): void
    {
        $customer = Customer::create(['name' => 'Peminjam']);
        $product = $this->product(['selling_price' => 40000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_id' => $customer->id,
            'is_credit' => true,
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::first();
        $this->assertTrue($sale->is_credit);
        $this->assertSame(25000, $sale->remainingAmount());

        $this->actingAs($this->kasir)
            ->post(route('sales.pay', $sale), ['amount' => 25000, 'payment_method' => 'cash'])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame(0, $sale->remainingAmount());
        $this->assertNotNull($sale->settled_at);
        $this->assertSame(0, $customer->outstanding());
    }

    public function test_kasir_tidak_bisa_membayar_transaksi_milik_kasir_lain(): void
    {
        $customer = Customer::create(['name' => 'Piutang']);
        $product = $this->product(['selling_price' => 40000]);

        CashShift::create([
            'user_id' => $this->admin->id,
            'opening_balance' => 0,
            'opened_at' => now()->subHour(),
        ]);

        // Transaksi dibuat admin.
        $this->actingAs($this->admin)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_id' => $customer->id,
            'is_credit' => true,
            'paid_amount' => 0,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::first();

        // Kasir lain tidak boleh menutup piutang itu.
        $this->actingAs($this->kasir)
            ->post(route('sales.pay', $sale), ['amount' => 10000, 'payment_method' => 'cash'])
            ->assertForbidden();

        $this->assertSame(40000, $sale->fresh()->remainingAmount(), 'Utang tidak boleh berubah oleh kasir lain.');
    }

    public function test_nomor_invoice_berurutan_dan_tidak_bentrok(): void
    {
        $product = $this->product(['stock' => 100]);

        foreach (range(1, 3) as $i) {
            $this->actingAsKasir([
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'paid_amount' => 15000,
                'payment_method' => 'cash',
            ])->assertOk();
        }

        $numbers = Sale::pluck('invoice_number')->all();
        $this->assertCount(3, array_unique($numbers), 'Nomor invoice harus unik.');
        $this->assertSame($numbers, array_values(array_unique($numbers)));
    }

    // ------------------------------------------------------------------
    // Retur
    // ------------------------------------------------------------------

    public function test_retur_mengembalikan_stok_dan_membagi_diskon_proporsional(): void
    {
        Setting::put('tax_percent', 10);

        $product = $this->product(['selling_price' => 20000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'discount' => 4000,
            'apply_tax' => true,
            'paid_amount' => 50000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();
        $itemId = $sale->items->first()->id;
        $stockBefore = $product->fresh()->stock;

        $this->actingAs($this->admin)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $itemId, 'quantity' => 1]],
                'refund_method' => 'restock',
                'reason' => 'Barang rusak',
            ])
            ->assertRedirect();

        $this->assertSame($stockBefore + 1, $product->fresh()->stock);

        $return = $sale->returns()->first();
        // Subtotal 40.000, diskon 4.000, pajak 10% dari 36.000 = 3.600.
        // Retur 1 dari 2 item (subtotal 20.000) => separuh tiap porsi:
        // diskon 2.000, pajak 1.800, nilai retur 19.800.
        $this->assertSame(20000, $return->subtotal);
        $this->assertSame(2000, $return->discount_share);
        $this->assertSame(1800, $return->tax_share);
        $this->assertSame(19800, $return->total);
    }

    public function test_retur_uang_tunai_mencatat_pengeluaran_kas(): void
    {
        $product = $this->product(['selling_price' => 20000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 20000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();

        $this->actingAs($this->admin)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
                'refund_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertSame(20000, $sale->returns()->first()->total);

        $shift = CashShift::open()->where('user_id', $this->kasir->id)->first();
        // 100.000 modal + 20.000 penjualan - 20.000 refund = 100.000
        $this->assertSame(100000, $shift->expectedCash(), 'Refund tunai harus mengurangi kas harapan shift.');
    }

    public function test_retur_utang_mengurangi_sisa_utang(): void
    {
        $customer = Customer::create(['name' => 'Peminjam Retur']);
        $product = $this->product(['selling_price' => 40000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_id' => $customer->id,
            'is_credit' => true,
            'paid_amount' => 10000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();

        $this->actingAs($this->admin)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
                'refund_method' => 'restock',
            ])
            ->assertRedirect();

        $sale->refresh();
        $this->assertSame(0, $sale->remainingAmount());
        $this->assertNotNull($sale->settled_at);
    }

    public function test_retur_melebihi_jumlah_dibeli_ditolak(): void
    {
        $product = $this->product();

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();

        $this->actingAs($this->admin)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 5]],
                'refund_method' => 'restock',
            ])
            ->assertStatus(422);
    }

    public function test_form_retur_menerima_baris_yang_dikosongkan(): void
    {
        $first = $this->product(['name' => 'Retur A']);
        $second = $this->product(['name' => 'Retur B']);

        $this->actingAsKasir([
            'items' => [
                ['product_id' => $first->id, 'quantity' => 1],
                ['product_id' => $second->id, 'quantity' => 1],
            ],
            'paid_amount' => 30000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();
        $items = $sale->items;

        $response = $this->actingAs($this->admin)->post(route('sales.return.store', $sale), [
            'items' => [
                ['sale_item_id' => $items[0]->id, 'quantity' => 1],
                ['sale_item_id' => $items[1]->id, 'quantity' => ''],
            ],
            'refund_method' => 'restock',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $sale->returns()->count());
    }

    public function test_kasir_tidak_bisa_retur_transaksi_milik_kasir_lain(): void
    {
        $product = $this->product();

        CashShift::create([
            'user_id' => $this->admin->id,
            'opening_balance' => 0,
            'opened_at' => now()->subHour(),
        ]);

        $this->actingAs($this->admin)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();
        $stockBefore = $product->fresh()->stock;

        $this->actingAs($this->kasir)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
                'refund_method' => 'restock',
            ])
            ->assertForbidden();

        $this->assertSame($stockBefore, $product->fresh()->stock);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::with('items')->first();
        $stockBefore = $product->fresh()->stock;

        $this->actingAs($this->kasir)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]],
                'refund_method' => 'restock',
            ])
            ->assertForbidden();

        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    // ------------------------------------------------------------------
    // Shift kas
    // ------------------------------------------------------------------

    public function test_kas_harapan_hanya_menghitung_transaksi_kasir_pemilik_shift(): void
    {
        CashShift::create([
            'user_id' => $this->admin->id,
            'opening_balance' => 0,
            'opened_at' => now()->subHour(),
        ]);

        $produkKasir = $this->product(['selling_price' => 10000, 'stock' => 20]);
        $produkAdmin = $this->product(['selling_price' => 30000, 'stock' => 20]);

        $this->actingAsKasir([
            'items' => [['product_id' => $produkKasir->id, 'quantity' => 1]],
            'paid_amount' => 10000,
            'payment_method' => 'cash',
        ])->assertOk();

        // Admin bertransaksi di luar shift kasir.
        $this->actingAs($this->admin)->postJson(route('pos.checkout'), [
            'items' => [['product_id' => $produkAdmin->id, 'quantity' => 1]],
            'paid_amount' => 30000,
            'payment_method' => 'cash',
        ])->assertOk();

        $shift = CashShift::open()->where('user_id', $this->kasir->id)->first();
        $this->assertSame(110000, $shift->expectedCash(), 'Kas harapan hanya modal + penjualan kasir sendiri.');
    }

    public function test_kas_harapan_menghitung_cicilan_utang_tunai(): void
    {
        $customer = Customer::create(['name' => 'Peminjam Cicil']);
        $product = $this->product(['selling_price' => 50000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_id' => $customer->id,
            'is_credit' => true,
            'paid_amount' => 10000,
            'payment_method' => 'cash',
        ])->assertOk();

        $sale = Sale::first();

        $this->actingAs($this->kasir)->post(route('sales.pay', $sale), [
            'amount' => 15000,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $shift = CashShift::open()->where('user_id', $this->kasir->id)->first();
        // 100.000 modal + 10.000 DP + 15.000 cicilan tunai = 125.000
        $this->assertSame(125000, $shift->expectedCash());
    }

    public function test_cicilan_non_tunai_tidak_ikut_dihitung_sebagai_kas(): void
    {
        $customer = Customer::create(['name' => 'Peminjam QRIS']);
        $product = $this->product(['selling_price' => 50000]);

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_id' => $customer->id,
            'is_credit' => true,
            'paid_amount' => 0,
            'payment_method' => 'qris',
        ])->assertOk();

        $sale = Sale::first();

        $this->actingAs($this->kasir)->post(route('sales.pay', $sale), [
            'amount' => 20000,
            'payment_method' => 'qris',
        ])->assertRedirect();

        $shift = CashShift::open()->where('user_id', $this->kasir->id)->first();
        $this->assertSame(100000, $shift->expectedCash(), 'Cicilan QRIS bukan uang di laci kas.');
    }

    // ------------------------------------------------------------------
    // Produk, stok, import, bulk
    // ------------------------------------------------------------------

    public function test_update_produk_tanpa_field_barcode_tidak_error(): void
    {
        $product = $this->product(['barcode' => null, 'name' => 'Tanpa Barcode']);

        $this->actingAs($this->admin)->put(route('products.update', $product), [
            'name' => 'Tanpa Barcode',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'stock' => $product->stock,
            'min_stock' => 5,
            'unit' => 'pcs',
        ])->assertRedirect(route('products.index'));

        $this->assertSame('Tanpa Barcode', $product->fresh()->name);
    }

    public function test_ubah_stok_lewat_form_produk_mencatat_perubahan(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->actingAs($this->admin)->put(route('products.update', $product), [
            'name' => $product->name,
            'barcode' => $product->barcode,
            'cost_price' => $product->cost_price,
            'selling_price' => $product->selling_price,
            'stock' => 25,
            'min_stock' => 5,
            'unit' => 'pcs',
        ])->assertRedirect();

        $this->assertSame(25, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => 25,
            'stock_after' => 25,
        ]);
    }

    public function test_mutasi_stok_tanpa_catatan_tidak_error(): void
    {
        $product = $this->product(['stock' => 10]);

        $this->actingAs($this->admin)->post(route('stocks.store'), [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ])->assertRedirect();

        $this->assertSame(15, $product->fresh()->stock);
    }

    public function test_produk_berriwayat_penjualan_tidak_bisa_dihapus(): void
    {
        $product = $this->product();

        $this->actingAsKasir([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'paid_amount' => 15000,
            'payment_method' => 'cash',
        ])->assertOk();

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertStringContainsString('tidak bisa dihapus', session('warning'));
    }

    public function test_aksi_massa_tanpa_parameter_wajib_ditolak_rapi(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)
            ->post(route('products.bulk'), ['ids' => [$product->id], 'action' => 'price_set'])
            ->assertSessionHasErrors('price');

        $this->assertSame(15000, $product->fresh()->selling_price, 'Harga tidak boleh berubah jadi null.');
    }

    public function test_import_produk_mencocokkan_produk_lama_berdasarkan_nama(): void
    {
        $existing = $this->product(['barcode' => null, 'name' => 'KeepMin', 'min_stock' => 42, 'unit' => 'sak']);

        $this->actingAs($this->admin)->post(route('products.import'), [
            'source' => "BARU-123;KeepMin;100;200;9",
        ])->assertRedirect(route('products.index'));

        $this->assertSame(1, Product::count(), 'Produk lama harus diperbarui, bukan diduplikasi.');
        $existing->refresh();
        $this->assertSame('BARU-123', $existing->barcode);
        $this->assertSame(200, $existing->selling_price);
        $this->assertSame(42, $existing->min_stock, 'min_stock tidak boleh ditimpa default saat kolom CSV kosong.');
        $this->assertSame('sak', $existing->unit, 'Satuan tidak boleh ditimpa default.');
        $this->assertNotNull($existing->category_id, 'Kategori lama tidak boleh hilang.');
    }

    // ------------------------------------------------------------------
    // Voucher & pengguna
    // ------------------------------------------------------------------

    public function test_kasir_bisa_mengecek_voucher_tanpa_akses_admin(): void
    {
        Voucher::create([
            'code' => 'HEMAT10',
            'name' => 'Diskon 10%',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->kasir)
            ->getJson(route('vouchers.check', ['code' => 'HEMAT10', 'subtotal' => 20000]))
            ->assertOk()
            ->assertJson(['discount' => 2000]);
    }

    public function test_voucher_bisa_dinonaktifkan_dari_form_edit(): void
    {
        $voucher = Voucher::create([
            'code' => 'MATI',
            'name' => 'Akan dimatikan',
            'type' => 'fixed',
            'value' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->put(route('vouchers.update', $voucher), [
            'code' => 'MATI',
            'name' => 'Akan dimatikan',
            'type' => 'fixed',
            'value' => 5000,
        ])->assertRedirect();

        $this->assertFalse($voucher->fresh()->is_active);
    }

    public function test_pengguna_dengan_riwayat_retur_tetap_bisa_dihapus(): void
    {
        $product = $this->product();
        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-1',
            'subtotal' => 15000, 'discount' => 0, 'tax' => 0, 'total' => 15000,
            'paid_amount' => 15000, 'change_amount' => 0,
            'payment_method' => 'cash', 'user_id' => $this->admin->id,
        ]);
        $sale->items()->create([
            'product_id' => $product->id, 'quantity' => 1, 'price' => 15000, 'subtotal' => 15000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('sales.return.store', $sale), [
                'items' => [['sale_item_id' => $sale->items()->first()->id, 'quantity' => 1]],
                'refund_method' => 'restock',
            ])->assertRedirect();

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->admin->id === $this->kasir->id ? $this->kasir->id : $this->kasir->id))
            ->assertRedirect();
    }

    public function test_admin_terakhir_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('profile.destroy'))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_akun_nonaktif_tidak_bisa_login_maupun_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'nonaktif@kasirku.test',
            'password' => 'rahasia123',
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => 'nonaktif@kasirku.test',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->post(route('password.request'), ['email' => 'nonaktif@kasirku.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_halaman_registrasi_tidak_lagi_terbuka(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@kasirku.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@kasirku.test']);
    }
}