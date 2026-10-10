# Kasirku 🏪

Aplikasi kasir (POS) + manajemen stok toko, open source, dibangun dengan Laravel + SQLite.

## Fitur

- **Kasir (POS)** — cari/scan produk, keranjang, hitung kembalian otomatis, struk siap cetak
- **Manajemen produk** — CRUD produk, barcode, kategori, harga beli/jual, stok minimum
- **Manajemen stok** — stok masuk/keluar/penyesuaian dengan riwayat pergerakan lengkap, peringatan stok menipis
- **Riwayat penjualan** — daftar transaksi + struk per transaksi
- **Laporan** — pendapatan, estimasi laba, penjualan harian, produk terlaris, filter rentang tanggal
- **Autentikasi** — login/register bawaan Laravel Breeze

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
default-src 'self'; script-src 'self' 'nonce-<random>' 'unsafe-eval';
style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:;
connect-src 'self'; worker-src 'self' blob:; object-src 'none';
base-uri 'self'; form-action 'self'; frame-ancestors 'self'
```

`'unsafe-eval'` masih diperlukan karena Alpine.js mengeksekusi expression
(`x-data`, `@click`, `x-text`), tapi `'unsafe-inline'` **sudah dihapus** untuk script:
semua `<script>` inline diberi `nonce="{{ $cspNonce }}"`, dan atribut event inline
(`onclick`/`onsubmit`) tidak lagi dipakai — semuanya dipindah ke Alpine atau helper.

## Struktur Database

| Tabel | Fungsi |
|---|---|
| `categories` | Kategori produk |
| `products` | Produk (barcode, harga, stok, stok minimum) |
| `stock_movements` | Riwayat stok masuk/keluar/penyesuaian |
| `sales` | Transaksi penjualan (invoice, total, bayar, kembalian) |
| `sale_items` | Item per transaksi |

## Kontribusi

PR terbuka. Untuk perubahan besar, buka issue dulu supaya bisa didiskusikan.

## Lisensi

[MIT](LICENSE.md)
