<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pembayaran') || Schema::hasTable('transaksi_tabungan')) {
            return;
        }

        Schema::create('school_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('spp_invoices')->nullOnDelete();
            $table->string('category');
            $table->string('direction');
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->date('transaction_date')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('pembayaran') || Schema::hasTable('transaksi_tabungan')) {
            return;
        }

        Schema::dropIfExists('school_transactions');
    }
};
