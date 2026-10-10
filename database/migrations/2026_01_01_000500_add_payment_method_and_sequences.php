<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instansi lama: sale_returns.user_id masih NOT NULL + restrict (BUG-09),
        // sehingga user yang pernah memproses retur tidak bisa dihapus.
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Cicilan utang ikut mencatat metode pembayaran (BUG-18):
        // tanpa ini, kas erwartan salah menghitung uang tunai di laci.
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('amount');
        });

        // Nomor invoice anti-race (BUG-25).
        Schema::create('sale_sequences', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_sequences');

        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};