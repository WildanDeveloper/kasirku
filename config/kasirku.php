<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versi Aplikasi
    |--------------------------------------------------------------------------
    | Naikkan nomor versi setiap ada rilis baru. Tampilan versi, deteksi
    | update, dan halaman "Apa Yang Baru" membaca nilai dari sini.
    */
    'version' => '1.4.6',

    'released_at' => '2026-10-11',

    'repository' => 'https://github.com/WildanDeveloper/kasirku',

    /*
    |--------------------------------------------------------------------------
    | Daftar Rilis
    |--------------------------------------------------------------------------
    | Kunci = nomor versi. Dipakai halaman "Apa Yang Baru".
    */
    'changelog' => [
        '1.4.6' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Pemeriksaan izin folder saat pembaruan kini akurat (membandingkan UID, bukan nama user).',
            ],
        ],
        '1.4.3' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Pemeriksaan izin folder sebelum pembaruan agar proses lebih informatif bila ada masalah izin file.',
            ],
        ],
        '1.4.2' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Pembaruan aplikasi kini berjalan penuh dari tombol Perbarui (kode, dependensi, aset, dan database).',
            ],
        ],
        '1.4.1' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Perbaikan kecil: tampilan bar versi dan alur pembaruan aplikasi lebih informatif.',
            ],
        ],
        '1.4.0' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Log aktivitas: catatan siapa mengubah produk, harga, stok, pelanggan, voucher, pengaturan, dan lainnya.',
                'Backup database: buat, unduh, hapus, dan pulihkan backup langsung dari menu Pengaturan (backup harian otomatis pukul 02:00).',
                'Printer struk thermal 58 mm / 80 mm dengan pilihan cetak struk otomatis.',
                'Dukungan scanner barcode USB/Bluetooth (fokus pencarian otomatis setelah scan).',
                'Retur barang: kembalikan stok, proporsional diskon/pajak, dan retur utang otomatis mengurangi piutang.',
                'Harga grosir per pelanggan + harga khusus per produk.',
                'Voucher promo (persen/nominal, kuota, masa berlaku).',
                'Dashboard ringkasan: omzet, laba, piutang, stok menipis, grafik 7 hari.',
                'Kartu produk & kartu pelanggan lengkap dengan riwayat.',
                'Cetak label harga massal.',
            ],
        ],
        '1.3.0' => [
            'date' => '11 Oktober 2026',
            'features' => [
                'Shift kasir: buka/tutup shift, modal awal, dan rekonsiliasi selisih kas.',
                'Transaksi ditahan (hold order) yang bisa dilanjutkan kasir lain.',
                'Utang/piutang pelanggan dengan cicilan.',
                'Role admin & kasir beserta tampilan menu per peran.',
                'Pengaturan toko, pengeluaran, dan laporan keuangan lengkap + ekspor CSV.',
            ],
        ],
        '1.2.0' => [
            'date' => '10 Oktober 2026',
            'features' => [
                'Bulk produk: import CSV, ubah harga/kategori/stok massal, hapus massal.',
                'Import produk dengan deteksi pemisah otomatis dan kategori opsional.',
                'Pengaturan keamanan: HSTS, CSP ketat tanpa unsafe-inline/eval, nonce per-request.',
            ],
        ],
        '1.0.0' => [
            'date' => '10 Oktober 2026',
            'features' => [
                'Rilis pertama: mesin kasir (POS), produk, stok, penjualan, laporan.',
            ],
        ],
    ],
];