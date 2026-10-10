/**
 * Komponen Alpine untuk UI pendukung.
 *
 * Semua helper yang butuh API browser (document, confirm, clipboard, print)
 * tinggal di sini supaya template cukup memanggil method — ekspresi Alpine
 * tetap aman untuk Alpine CSP build (tanpa eval).
 */

/** Konfirmasi sebelum submit form (menggantikan onsubmit inline). */
export function confirmSubmit() {
    return {
        ask(event, message) {
            if (! confirm(message)) event.preventDefault();
        },
    };
}

/** Gabungan beberapa helper untuk halaman tabel. */
export function tableTools() {
    return {
        copied: '',

        toggleEdit(id) {
            const row = document.getElementById(id);
            if (row) row.classList.toggle('hidden');
        },
        ask(event, message) {
            if (! confirm(message)) event.preventDefault();
        },
        copy(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code);
            }

            this.copied = code;
        },
    };
}

/** Tombol cetak struk / label. */
export function printable() {
    return {
        init() {
            if (this.$root.dataset.autoprint === '1') {
                setTimeout(() => this.printPage(), 300);
            }
        },

        /** Ukuran kertas thermal 58/80mm diterapkan hanya saat mencetak. */
        applyPaperSize() {
            const paper = this.$root.dataset.paper || '58';

            document.body.classList.add('print-receipt', 'receipt-' + paper);
        },

        clearPaperSize() {
            document.body.classList.remove('print-receipt', 'receipt-58', 'receipt-80', 'receipt-a4');
        },

        printPage() {
            this.applyPaperSize();
            window.print();

            // Bersihkan setelah dialog cetak ditutup.
            setTimeout(() => this.clearPaperSize(), 500);
            window.addEventListener('afterprint', () => this.clearPaperSize(), { once: true });
        },
    };
}

/** Salin kode voucher ke clipboard. */
export function copyable() {
    return {
        copied: '',

        copy(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code);
            }

            this.copied = code;
        },
    };
}

/** Buka/tutup panel edit baris tabel (produk? pengguna, voucher). */
export function rowEditor() {
    return {
        toggleEdit(id) {
            const row = document.getElementById(id);
            if (row) row.classList.toggle('hidden');
        },
    };
}

/** Bar versi: cek versi baru di server dan jalankan pembaruan. */
export function appVersion() {
    return {
        updateAvailable: false,
        latestVersion: '',
        canUpdate: false,
        statusUrl: '',
        applyUrl: '',
        whatsNewUrl: '/',

        init() {
            this.statusUrl = this.$root.dataset.statusUrl ?? '';
            this.applyUrl = this.$root.dataset.applyUrl ?? '';
            this.whatsNewUrl = this.$root.dataset.whatsNewUrl ?? '/';
            this.canUpdate = this.$root.dataset.canUpdate === '1';

            this.check();
        },

        async check() {
            if (!this.statusUrl) return;

            try {
                const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;

                const data = await response.json();
                this.updateAvailable = !!data.update_available;
                this.latestVersion = data.latest_version ?? '';
            } catch (e) {
                // Offline / API GitHub tak terjangkau: sembunyikan tombol update.
            }
        },

        applyUpdate() {
            if (!confirm('Perbarui aplikasi ke versi ' + this.latestVersion + '?\n\nHalaman mungkin sempat tidak tersedia selama proses.')) {
                return;
            }

            this.updateAvailable = false;
            this.$root.dispatchEvent(new CustomEvent('kasirku:apply-update', { bubbles: true }));

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.applyUrl;
            form.innerHTML = '<input type="hidden" name="_token" value="' + document.querySelector('meta[name=csrf-token]').content + '">';
            document.body.appendChild(form);
            form.submit();
        },
    };
}

/** Dropdown Breeze. */
export function dropdown() {
    return {
        open: false,

        /** Logout dari dropdown: kirim form tanpa handler inline (CSP). */
        doLogout() {
            this.open = false;
            this.$root.closest('form').submit();
        },
    };
}

/** Modal Breeze (fokus trap + scroll lock). */
export function modal() {
    return {
        show: false,
        name: '',

        init() {
            this.name = this.$root.dataset.modalName ?? '';
            this.show = this.$root.dataset.modalOpen === '1';

            this.$watch('show', (value) => {
                if (value) {
                    document.body.classList.add('overflow-y-hidden');

                    if (this.$root.dataset.modalFocusable === '1') {
                        setTimeout(() => this.firstFocusable()?.focus(), 100);
                    }
                } else {
                    document.body.classList.remove('overflow-y-hidden');
                }
            });
        },

        focusables() {
            const selector =
                "a, button, input:not([type='hidden']), textarea, select, details, [tabindex]:not([tabindex='-1'])";

            return [...this.$root.querySelectorAll(selector)]
                .filter((el) => !el.hasAttribute('disabled'));
        },

        firstFocusable() {
            return this.focusables()[0];
        },

        lastFocusable() {
            return this.focusables().slice(-1)[0];
        },

        nextFocusable() {
            return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable();
        },

        prevFocusable() {
            return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable();
        },

        nextFocusableIndex() {
            return (this.focusables().indexOf(document.activeElement) + 1)
                % (this.focusables().length + 1);
        },

        prevFocusableIndex() {
            return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1;
        },

        /** Dipakai tombol "Batal"/x untuk menutup. */
        close() {
            this.show = false;
        },

        handleOpen(detail) {
            if (detail == this.name) this.show = true;
        },

        handleClose(detail) {
            if (detail == this.name) this.show = false;
        },

        syncName() {
            this.name = this.$root.dataset.modalName ?? '';
        },
    };
}

/** Pesan sukses yang menghilang sendiri. */
export function flash() {
    return {
        show: true,

        init() {
            if (this.$root.dataset.flashVisible !== '1') {
                this.show = false;
                return;
            }

            setTimeout(() => {
                this.show = false;
            }, 2500);
        },
    };
}

/** Aksi massal pada daftar produk. */
export function bulkProducts(ids) {
    return {
        selected: [],
        action: 'category',
        allIds: ids,

        ask(event, message) {
            if (! confirm(message)) event.preventDefault();
        },

        askBulkDelete(event) {
            if (this.action === 'delete'
                && ! confirm('Hapus ' + this.selected.length + ' produk terpilih?')) {
                event.preventDefault();
            }
        },

        toggleAll(checked) {
            this.selected = checked ? [...this.allIds] : [];
        },

        get selectedCount() {
            return this.selected.length;
        },
    };
}
/**
 * Form pembelian: baris barang dinamis dengan subtotal & total otomatis.
 * Template baris memakai placeholder __i__ yang diganti ke indeks array
 * agar cocok dengan validasi Laravel (items[0][product_id]).
 */
export function purchaseForm(products) {
    return {
        products,
        rows: [{ product_id: '', quantity: 1, cost_price: 0 }],
        discount: 0,
        paid: 0,

        get subtotal() {
            return this.rows.reduce((sum, row) => sum + (+row.quantity || 0) * (+row.cost_price || 0), 0);
        },

        get total() {
            return Math.max(0, this.subtotal - (+this.discount || 0));
        },

        get outstanding() {
            return Math.max(0, this.total - (+this.paid || 0));
        },

        addRow() {
            this.rows.push({ product_id: '', quantity: 1, cost_price: 0 });
            this.syncNames();
        },

        removeRow(index) {
            if (this.rows.length > 1) this.rows.splice(index, 1);
            this.syncNames();
        },

        /**
         * Samakan atribut name tiap baris dengan indeksnya supaya Laravel
         * membaca items[0], items[1], ... (bukan __i__).
         */
        syncNames() {
            this.$root.querySelectorAll('tbody tr').forEach((tr, i) => {
                tr.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/items\[[^\]]+\]/, `items[${i}]`);
                });
            });
        },

        init() {
            this.$nextTick(() => this.syncNames());
        },

        format(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
        },
    };
}

/**
 * Harga grosir bertingkat di form produk. Baris awal diambil dari tier
 * yang sudah tersimpan; atribut name memakai __i__ yang disinkronkan ke
 * indeks array agar cocok dengan validasi Laravel (tiers[0][min_qty]).
 */
export function priceTiers() {
    return {
        tiers: JSON.parse(this.$root.dataset.tiers || '[]'),

        addTier() {
            const last = this.tiers[this.tiers.length - 1];

            this.tiers.push({
                min_qty: last ? Number(last.min_qty) + 1 : 1,
                price: last ? Number(last.price) : 0,
            });
            this.syncNames();
        },

        removeTier(index) {
            this.tiers.splice(index, 1);
            this.syncNames();
        },

        /** Samakan atribut name setiap baris dengan indeksnya (tiers[0], tiers[1], ...). */
        syncNames() {
            this.$nextTick(() => {
                this.$root.querySelectorAll('[name^="tiers["]').forEach((field) => {
                    const row = field.closest('[data-tier-row]');
                    const index = Number(row?.dataset.tierRow ?? -1);

                    if (index >= 0) {
                        field.name = field.name.replace(/tiers\[[^\]]+\]/, `tiers[${index}]`);
                    }
                });
            });
        },

        init() {
            this.syncNames();
        },
    };
}

/**
 * Ringkasan struk untuk dikirim lewat WhatsApp (tautan wa.me) atau disalin.
 * Teks dirakit di sisi klien supaya tidak perlu endpoint tambahan.
 */
export function receiptShare() {
    return {
        get summary() {
            const node = this.$root.querySelector('[data-summary]');

            try {
                return JSON.parse(node?.dataset.summary || '{}');
            } catch (error) {
                return {};
            }
        },

        message() {
            const s = this.summary;

            if (!s.invoice) return '';

            const lines = [
                `*${s.store}*`,
                `No: ${s.invoice}`,
                `${s.date}${s.cashier ? ' · ' + s.cashier : ''}`,
            ];

            if (s.customer) lines.push(`Pelanggan: ${s.customer}`);

            lines.push('', ...(s.items || []));

            lines.push('', `Subtotal: Rp ${s.subtotal}`);

            if (Number(s.discount) > 0) lines.push(`Diskon: -Rp ${Number(s.discount).toLocaleString('id-ID')}`);

            if (Number(s.tax) > 0) lines.push(`Pajak: Rp ${s.tax}`);

            lines.push(`*Total: Rp ${s.total}*`);

            if (Number(s.remaining) > 0) {
                lines.push(`Sisa utang: Rp ${Number(s.remaining).toLocaleString('id-ID')}`);
            }

            lines.push('', `Detail: ${s.url}`);

            return lines.join('\n');
        },

        waLink() {
            const phone = (this.summary.customerPhone || '').replace(/[^0-9]/g, '');

            return `https://wa.me/${phone}?text=${encodeURIComponent(this.message())}`;
        },

        async copyText() {
            try {
                await navigator.clipboard.writeText(this.message());
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch (error) {
                // Clipboard bisa ditolak browser; abaikan tanpa merusak halaman.
            }
        },
    };
}
