<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('selling_price')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(5);
            $table->string('unit', 20)->default('pcs');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->integer('quantity');
            $table->unsignedBigInteger('stock_before');
            $table->unsignedBigInteger('stock_after');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->enum('payment_method', ['cash', 'qris', 'debit'])->default('cash');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
