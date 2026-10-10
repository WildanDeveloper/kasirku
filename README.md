# Kasirku 🏪

Aplikasi kasir (POS) + manajemen stok toko, open source, dibangun dengan Laravel + SQLite.

## Fitur

- **Kasir (POS)** — cari/scan produk, keranjang, hitung kembalian otomatis, struk siap cetak
- **Manajemen produk** — CRUD produk, barcode, kategori, harga beli/jual, stok minimum
- **Manajemen stok** — stok masuk/keluar/penyesuaian dengan riwayat pergerakan lengkap, peringatan stok menipis
- **Riwayat penjualan** — daftar transaksi + struk per transaksi
- **Laporan** — pendapatan, estimasi laba, penjualan harian, produk terlaris, filter rentang tanggal
- **Autentikasi** — login Laravel Breeze (tanpa registrasi publik; kasir dibuat dari menu Pengguna)

## Teknologi

- Laravel 13
- SQLite (default, ganti MySQL/Postgres via `.env` tanpa ubah kode)
- Tailwind CSS + Alpine.js
- Laravel Breeze

## Instalasi

```sh
git clone https://github.com/USERNAME/kasirku.git
cd kasirku
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Login bawaan dari seeder:

- Email: `admin@kasirku.test`
- Password: `password`

## Keamanan

- `APP_DEBUG=false` di produksi (lihat `.env.example`)
- Header keamanan dipasang di level nginx untuk domain HTTPS:

| Header | Nilai |
|---|---|
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` (anti clickjacking) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | kamera hanya untuk origin sendiri (dipakai fitus scan barcode) |
| `Content-Security-Policy` | dikirim oleh Laravel (middleware `AddContentSecurityPolicy`) dengan **nonce per-request** untuk script inline |

Detail CSP yang dikirim:

```
default-src 'self'; script-src 'self' 'nonce-<random>'; style-src 'self';
img-src 'self' data: blob:; connect-src 'self'; worker-src 'self' blob:;
object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'
```

Tidak ada lagi `unsafe-eval` maupun `unsafe-inline`:

- Alpine memakai **build CSP** (`@alpinejs/csp`), bukan `eval`-based. Ekspresi di
  template dibatasi pada operasi sederhana, jadi logika aplikasi dipindah ke
  komponen JS di `resources/js/components/` dan didaftarkan lewat `Alpine.data()`.
- Atribut event inline (`onclick`/`onsubmit`) dihapus; konfirmasi hapus, cetak,
  salin kode voucher, dan buka/tutup baris edit semuanya lewat method Alpine.
- Tidak ada atribut `style="..."` lagi; grafik dashboard/laporan memakai elemen
  `<svg>` (atribut `y`/`height`), dan latar grid login dipindah ke kelas CSS.
- Aset Alpine sendiri tidak memanggil `eval`/`new Function` (terverifikasi di bundle).

Konsekuensi menulis komponen Alpine di proyek ini:

```js
// resources/js/components/ui.js
export function tableTools() {
    return {
        ask(event, message) {
            if (! confirm(message)) event.preventDefault();   // API browser di dalam method
        },
    };
}
```

```blade
<div x-data="tableTools">
    <form @submit="ask($event, 'Hapus data ini?')">...</form>
</div>
```

Ekspresi seperti `document`, `window`, `Math`, arrow function (`=>`), template
literal, dan `if (...)` **tidak boleh** dipakai di atribut `x-*` — pindahkan ke
method komponen.

## Pengujian

```bash
php artisan test
```

## Struktur Database

| Tabel | Fungsi |
|---|---|
| `categories` | Kategori produk |
| `products` | Produk (barcode, harga, stok, stok minimum) |
| `stock_movements` | Riwayat stok masuk/keluar/penyesuaian |
| `sales` | Transaksi penjualan (invoice, total, bayar, kembalian) |
| `sale_items` | Item per transaksi |
| `sale_sequences` | Penomoran invoice harian (anti-race) |
| `sale_payments` | Cicilan pembayaran utang (+ metode pembayaran) |
| `held_sales` | Transaksi yang ditahan kasir |
| `cash_shifts` | Shift kasir & selisih rekonsiliasi |
| `customers` | Pelanggan (flag grosir, sisa utang) |
| `customer_prices` | Harga khusus per pelanggan |
| `vouchers` | Kode promo (persen/nominal, kuota, masa berlaku) |
| `expenses` | Pengeluaran toko |
| `settings` | Identitas toko, pajak, footer struk |
| `sale_returns` / `sale_return_items` | Retur penjualan |
| `activity_logs` | Log aktivitas (audit trail) |

## Kontribusi

PR terbuka. Untuk perubahan besar, buka issue dulu supaya bisa didiskusikan.

## Lisensi

[MIT](LICENSE.md)

## Pembaruan Aplikasi

- Versi aplikasi tampil di header, lengkap dengan tombol **Perbarui** (muncul bila
  ada versi baru) dan tombol **Apa Yang Baru** yang membuka daftar fitur per rilis.
- Tombol Perbarui menjalankan: `git pull` → `composer install` → `npm ci` →
  `npm run build` → `php artisan migrate --force` → `php artisan optimize:clear`.
  Log tiap langkah tersimpan di `storage/app/logs/` dan `storage/logs/laravel.log`.
- Nomor versi ada di `config/kasirku.php` (`version` + `changelog`). Naikkan versi
  dan buat GitHub Release dengan tag yang sama agar tombol Perbarui muncul.

## Perangkat Kasir

### Scanner barcode USB / Bluetooth

Scanner barcode USB/Bluetooth bekerja sebagai papan ketik (HID) dan **tidak perlu
konfigurasi khusus** — aplikasi sudah memfokuskan di kotak pencarian:

1. Pasang scanner ke komputer/tablet (USB atau Bluetooth).
2. Atur suffix scanner jadi **Enter** (default sebagian besar sudah).
3. Di halaman **Kasir**, arahkan scanner ke barcode → produk langsung masuk keranjang.

Scanner bisa juga memakai kamera HP lewat tombol **Scan** (butuh izin kamera).

### Printer struk thermal 58/80 mm

Menu **Pengaturan → Lebar Struk**:

| Lebar | Ukuran kertas | Ukuran font saat cetak |
|---|---|---|
| `58` | 58 mm | 11 px monospace, margin 4 mm |
| `80` | 80 mm | 11 px monospace, margin 4 mm |
| `a4` | A4 penuh | seperti halaman web |

Aktifkan **Cetak struk otomatis** agar struk langsung tercetak setelah transaksi.
Ukuran kertas diterapkan hanya saat dialog cetak dibuka, jadi halaman lain tetap
normal. Gunakan dialog print browser (Chrome: *Destination → Save as PDF* kalau
ingin cetak ke PDF).

## Backup Database

Menu **Pengaturan → Backup Database** (khusus admin):

- **Backup Sekarang** — menyalin `database.sqlite` ke `storage/app/backups`
- **Unduh** / **Hapus** file backup
- **Pulihkan** dari file `.sqlite` (backup kondisi saat ini dibuat otomatis lebih dulu)

Backup harian otomatis lewat cron:

```
0 2 * * * cd /var/www/kasirku && php artisan kasirku:backup --keep=14 >/dev/null 2>&1
```

## Log Aktivitas

Menu **Log Aktivitas** mencatat siapa mengubah apa dan kapan: produk & harga, stok,
pelanggan (termasuk harga khusus), voucher, pengeluaran, pengaturan toko, pengguna,
retur, pembayaran utang, shift kas, dan backup database.
