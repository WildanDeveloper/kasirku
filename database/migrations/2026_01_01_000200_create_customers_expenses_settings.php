<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('category')->default('lainnya');
            $table->unsignedBigInteger('amount');
            $table->date('spent_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->default(0)->after('invoice_number');
            $table->unsignedBigInteger('discount')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax')->default(0)->after('discount');
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['subtotal', 'discount', 'tax']);
        });

        Schema::dropIfExists('settings');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('customers');
    }
};
