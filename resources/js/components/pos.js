/**
 * Komponen Alpine untuk halaman Kasir.
 *
 * Semua logika Kasir dipindahkan ke sini agar halaman bisa memakai Alpine CSP
 * build (tanpa eval): ekspresi di template hanya boleh memanggil method.
 * Data awal (produk, pelanggan, voucher, URL) dibaca dari atribut data-* root.
 */
export default function pos() {
    return {
        products: [],
        customers: [],
        customPrices: {},
        vouchers: [],
        taxPercent: 0,
        openShift: false,
        heldSales: [],
        search: '',
        cart: [],
        paymentMethod: 'cash',
        discountInput: 0,
        customerId: '',
        applyTax: false,
        isCredit: false,
        paidAmount: 0,
        loading: false,
        message: '',
        success: false,
        receiptUrl: '',
        heldNote: '',
        tiers: {},
        loyaltyMaxRedeemPercent: 50,
        redeemPoints: 0,
        useDeposit: 0,
        voucherCode: '',
        voucherName: '',
        voucherDiscount: 0,
        voucherMessage: '',
        voucherError: false,
        scanner: null,
        scannerOpen: false,
        scannerError: '',

        urls: {
            checkout: '',
            hold: '',
            holdDestroy: '',
            voucherCheck: '',
        },

        init() {
            const data = this.$root.dataset;

            this.products = this.parse(data.products) ?? [];
            this.customers = this.parse(data.customers) ?? [];
            this.customPrices = this.parse(data.customPrices) ?? {};
            this.vouchers = this.parse(data.vouchers) ?? [];
            this.tiers = this.parse(data.tiers) ?? {};
            this.loyaltyMaxRedeemPercent = Number(data.loyaltyMaxRedeemPercent || 50);
            this.taxPercent = Number(data.taxPercent || 0);
            this.openShift = data.openShift === '1';
            this.heldSales = this.parse(data.heldSales) ?? [];

            this.urls = {
                checkout: data.checkoutUrl,
                hold: data.holdUrl,
                holdDestroy: data.holdDestroyUrl,
                voucherCheck: data.voucherCheckUrl,
            };
        },

        parse(value) {
            if (!value) return null;

            try {
                return JSON.parse(value);
            } catch (e) {
                return null;
            }
        },

        get filtered() {
            const s = this.search.toLowerCase().trim();
            if (!s) return this.products;

            return this.products.filter((p) =>
                p.name.toLowerCase().includes(s) || (p.barcode || '').includes(s)
            );
        },

        /** Harga dasar tier untuk jumlah tertentu (ecer bila tak ada tier). */
        tierPrice(product, quantity) {
            const tiers = this.tiers[product.id] ?? [];
            let price = product.price_wholesale || product.selling_price;

            for (const tier of tiers) {
                if (tier.min_qty <= quantity) {
                    price = tier.price;
                    break;
                }
            }

            return price;
        },

        /** Harga produk: khusus pelanggan > tier grosir > grosir > ecer. */
        priceOf(product, quantity) {
            const qty = quantity ?? (this.cart.find((i) => i.id === product.id)?.quantity ?? 1);

            if (!this.customerId) {
                const tiers = this.tiers[product.id] ?? [];
                return tiers.length ? this.tierPrice(product, qty) : product.selling_price;
            }

            const customer = this.customers.find((c) => c.id == this.customerId);
            if (!customer) return product.selling_price;

            const custom = this.customPrices[customer.id + ':' + product.id];
            if (custom) return custom;

            if (customer.is_wholesale) return this.tierPrice(product, qty);

            const tiers = this.tiers[product.id] ?? [];
            return tiers.length ? this.tierPrice(product, qty) : product.selling_price;
        },

        get customer() {
            return this.customers.find((c) => c.id == this.customerId) ?? null;
        },

        get selectedCustomer() {
            return this.customer ?? null;
        },

        get pointsBalance() {
            return this.customer ? this.customer.points : 0;
        },

        get pointValue() {
            return this.customer ? (this.customer.points_value || 100) : 100;
        },

        get pointsDiscount() {
            const points = Math.min(this.redeemPoints || 0, this.pointsBalance);
            return points * this.pointValue;
        },

        get depositBalance() {
            return this.customer ? this.customer.deposit : 0;
        },

        get depositUsed() {
            return Math.max(0, Math.min(this.useDeposit || 0, this.depositBalance));
        },

        get isWholesale() {
            return this.customer ? this.customer.is_wholesale : false;
        },

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + this.priceOf(item) * item.quantity, 0);
        },

        get discount() {
            return Math.max(0, Number(this.discountInput) || 0);
        },

        get taxableBase() {
            return Math.max(0, this.subtotal - this.discount - this.voucherDiscount - this.pointsDiscount);
        },

        get cashDue() {
            return Math.max(0, this.total - this.depositUsed);
        },

        get taxAmount() {
            if (!this.applyTax) return 0;

            return Math.round(this.taxableBase * this.taxPercent / 100);
        },

        get total() {
            return this.taxableBase + this.taxAmount;
        },

        format(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        /** Kembalian tunai (tanpa global Math agar aman untuk Alpine CSP). */
        changeText() {
            return this.format(Math.max(this.paidAmount - this.total, 0));
        },

        /** Sisa utang untuk transaksi kredit. */
        remainingText() {
            return this.format(Math.max(this.total - this.paidAmount, 0));
        },

        add(product) {
            if (product.stock === 0) return;

            const existing = this.cart.find((i) => i.id === product.id);
            if (existing) {
                if (existing.quantity < product.stock) existing.quantity++;
            } else {
                this.cart.push({ ...product, quantity: 1 });
            }
        },

        addFromSearch() {
            const exact = this.products.find((p) => p.barcode && p.barcode === this.search.trim());
            const product = exact || this.filtered[0];

            if (product) {
                this.add(product);
                this.search = '';
                this.focusScanner();
            }
        },

        addByBarcode(code) {
            const product = this.products.find((p) => p.barcode && p.barcode === code);

            if (product) {
                this.add(product);
                this.focusScanner();

                return true;
            }

            return false;
        },

        /** Fokuskan lagi kotak pencarian supaya scanner USB siap untuk scan berikutnya. */
        focusScanner() {
            setTimeout(() => this.$refs.search?.focus(), 50);
        },

        inc(item) {
            if (item.quantity < item.stock) item.quantity++;
        },

        dec(item) {
            if (item.quantity > 1) item.quantity--;
        },

        removeItem(index) {
            this.cart.splice(index, 1);
        },

        clearCart() {
            this.cart = [];
        },

        clamp(item) {
            if (!item.quantity || item.quantity < 1) item.quantity = 1;
            if (item.quantity > item.stock) item.quantity = item.stock;
        },

        async startScanner() {
            this.scannerOpen = true;
            this.scannerError = '';

            try {
                const Html5Qrcode = await window.loadQrScanner();

                this.$nextTick(async () => {
                    this.scanner = new Html5Qrcode('qr-reader');
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 140 } },
                        (decoded) => {
                            if (this.addByBarcode(decoded)) {
                                this.stopScanner();
                            } else {
                                this.scannerError = 'Barcode ' + decoded + ' tidak ditemukan.';
                            }
                        },
                        () => {}
                    );
                });
            } catch (e) {
                this.scannerError = 'Kamera tidak bisa diakses. Pastikan izin kamera diizinkan, atau ketik barcode secara manual.';
            }
        },

        async stopScanner() {
            if (this.scanner) {
                try {
                    await this.scanner.stop();
                    this.scanner.clear();
                } catch (e) {
                    // Scanner mungkin belum pernah start.
                }

                this.scanner = null;
            }

            this.scannerOpen = false;
            this.scannerError = '';
        },

        async applyVoucher() {
            const code = this.voucherCode.trim();
            if (!code) return;

            this.voucherMessage = '';

            const url = this.urls.voucherCheck.replace(':code', encodeURIComponent(code))
                + '?subtotal=' + this.taxableBase;

            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                this.voucherError = true;
                this.voucherMessage = data.message || 'Voucher tidak valid.';
                return;
            }

            this.voucherError = false;
            this.voucherCode = data.code;
            this.voucherName = data.code + ' (' + data.label + ')';
            this.voucherDiscount = data.discount;
            this.voucherMessage = 'Voucher ' + data.code + ' dipakai: potongan ' + this.format(data.discount) + '.';
        },

        removeVoucher() {
            this.voucherCode = '';
            this.voucherName = '';
            this.voucherDiscount = 0;
            this.voucherMessage = '';
        },

        holdUrlFor(held) {
            return this.urls.holdDestroy.replace(':id', held.id);
        },

        async holdOrder() {
            const response = await fetch(this.urls.hold, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({
                    items: this.cart.map((i) => ({ product_id: i.id, quantity: i.quantity })),
                    discount: this.discount,
                    customer_id: this.customerId || null,
                    note: this.heldNote || null,
                }),
            });

            if (!response.ok) return;

            const data = await response.json();

            this.heldSales.unshift({
                id: data.id,
                items: this.cart.map((i) => ({ product_id: i.id, quantity: i.quantity })),
                discount: this.discount,
                customer_id: this.customerId || null,
                note: this.heldNote || null,
            });

            this.cart = [];
            this.discountInput = 0;
            this.customerId = '';
            this.heldNote = '';
        },

        resumeHeld(held) {
            for (const item of held.items) {
                const product = this.products.find((p) => p.id === item.product_id);
                if (!product) continue;

                const existing = this.cart.find((i) => i.id === product.id);
                if (existing) {
                    existing.quantity = Math.min(existing.quantity + item.quantity, product.stock);
                } else {
                    this.cart.push({ ...product, quantity: Math.min(item.quantity, product.stock) });
                }
            }

            this.discountInput = held.discount || 0;
            this.customerId = held.customer_id || '';
            this.heldNote = held.note || '';
            this.heldSales = this.heldSales.filter((h) => h.id !== held.id);

            fetch(this.holdUrlFor(held), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });
        },

        async deleteHeld(held) {
            const response = await fetch(this.holdUrlFor(held), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() },
            });

            if (response.ok) {
                this.heldSales = this.heldSales.filter((h) => h.id !== held.id);
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async checkout() {
            this.loading = true;
            this.message = '';
            this.receiptUrl = '';

            try {
                const response = await fetch(this.urls.checkout, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({
                        items: this.cart.map((i) => ({ product_id: i.id, quantity: i.quantity })),
                        discount: this.discount,
                        customer_id: this.customerId || null,
                        apply_tax: this.applyTax,
                        is_credit: this.isCredit && !!this.customerId,
                        voucher_code: this.voucherCode || null,
                        redeem_points: Number(this.redeemPoints) || 0,
                        use_deposit: this.depositUsed,
                        paid_amount: this.isCredit
                            ? Math.min(this.paidAmount || 0, this.cashDue)
                            : Math.max(this.paidAmount, this.cashDue),
                        payment_method: this.paymentMethod,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Checkout gagal.');
                }

                this.success = true;
                this.message = this.isCredit
                    ? 'Transaksi utang dicatat. Sisa: ' + this.format(data.remaining)
                    : 'Transaksi berhasil. Kembalian: ' + this.format(data.change_amount);
                this.receiptUrl = data.receipt_url;

                for (const item of this.cart) {
                    const product = this.products.find((p) => p.id === item.id);
                    if (product) product.stock -= item.quantity;
                }

                this.cart = [];
                this.discountInput = 0;
                this.customerId = '';
                this.applyTax = false;
                this.isCredit = false;
                this.paidAmount = 0;
                this.removeVoucher();
            } catch (error) {
                this.success = false;
                this.message = error.message;
            } finally {
                this.loading = false;
            }
        },
    };
}