<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = ['properties' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectLabel(): string
    {
        if (! $this->subject_type) {
            return '-';
        }

        return class_basename($this->subject_type).' #'.$this->subject_id;
    }

    public static function labels(): array
    {
        return [
            'product.created' => 'Produk dibuat',
            'product.updated' => 'Produk diubah',
            'product.deleted' => 'Produk dihapus',
            'product.bulk' => 'Aksi massal produk',
            'product.import' => 'Import produk',
            'product.stock' => 'Stok diubah',
            'price.changed' => 'Harga diubah',
            'customer.created' => 'Pelanggan ditambahkan',
            'customer.updated' => 'Pelanggan diubah',
            'customer.deleted' => 'Pelanggan dihapus',
            'customer.price' => 'Harga khusus pelanggan',
            'sale.returned' => 'Retur penjualan',
            'sale.paid' => 'Utang dibayar',
            'voucher.created' => 'Voucher dibuat',
            'voucher.updated' => 'Voucher diperbarui',
            'voucher.deleted' => 'Voucher dihapus',
            'expense.created' => 'Pengeluaran dicatat',
            'expense.deleted' => 'Pengeluaran dihapus',
            'settings.updated' => 'Pengaturan toko diubah',
            'user.created' => 'User dibuat',
            'user.updated' => 'User diperbarui',
            'user.deleted' => 'User dihapus',
            'shift.opened' => 'Shift kas dibuka',
            'shift.closed' => 'Shift kas ditutup',
            'backup.created' => 'Backup database dibuat',
            'backup.restored' => 'Backup database dipulihkan',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->action] ?? $this->action;
    }
}