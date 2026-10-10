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
