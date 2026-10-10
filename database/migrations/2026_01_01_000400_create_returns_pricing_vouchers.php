<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_admin');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price_wholesale')->nullable()->after('selling_price');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_wholesale')->default(false)->after('address');
        });

        Schema::create('customer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['percent', 'fixed']);
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_purchase')->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('voucher_discount')->default(0)->after('discount');
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('discount_share')->default(0);
            $table->unsignedInteger('voucher_share')->default(0);
            $table->unsignedInteger('tax_share')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->enum('refund_method', ['cash', 'restock'])->default('cash');
            $table->unsignedInteger('debt_reduction')->default(0);
            $table->string('reason')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('price');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['voucher_discount']);
        });
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('customer_prices');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_wholesale');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_wholesale');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};