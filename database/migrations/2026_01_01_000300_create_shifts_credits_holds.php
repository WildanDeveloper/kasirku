<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('remember_token');
        });

        Schema::create('held_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('items');
            $table->unsignedBigInteger('discount')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_credit')->default(false)->after('change_amount');
            $table->timestamp('settled_at')->nullable()->after('is_credit');
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('opening_balance')->default(0);
            $table->unsignedBigInteger('expected_balance')->nullable();
            $table->unsignedBigInteger('closing_balance')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('sale_payments');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_credit', 'settled_at']);
        });
        Schema::dropIfExists('held_sales');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
