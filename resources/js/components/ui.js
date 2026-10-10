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