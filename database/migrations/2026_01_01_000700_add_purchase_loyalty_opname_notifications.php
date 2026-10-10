<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- Harga grosir bertingkat ----------
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->unique(['product_id', 'min_qty']);
        });

        // ---------- Poin loyalitas ----------
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('points')->default(0)->after('is_wholesale');
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['earn', 'redeem', 'adjust']);
            $table->integer('points'); // bisa negatif saat tukar
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        // ---------- Kasbon / deposit pelanggan ----------
        Schema::create('customer_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->unsignedBigInteger('amount');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        // ---------- Supplier & pembelian ----------
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->enum('status', ['received', 'cancelled'])->default('received');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('cost_price');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // HPP historis: harga beli saat barang terjual (bukan harga beli sekarang).
        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('cost_at_sale')->nullable()->after('price');
        });

        // ---------- Stok opname ----------
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'finished'])->default('draft');
            $table->unsignedBigInteger('difference_value')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('system_qty');
            $table->unsignedInteger('physical_qty')->nullable();
            $table->integer('difference')->default(0);
            $table->timestamps();
        });

        // ---------- Notifikasi ----------
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable();
            $table->enum('level', ['info', 'warning', 'danger'])->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('cost_at_sale'));
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customer_deposits');
        Schema::dropIfExists('loyalty_transactions');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('points'));
        Schema::dropIfExists('price_tiers');
    }
};