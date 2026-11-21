<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('setoran_tabungan')) {
            return;
        }

        Schema::create('setoran_tabungan', function (Blueprint $table): void {
            $table->id();
            $table->integer('id_siswa')->index();
            $table->decimal('jumlah', 14, 2);
            $table->string('metode', 20)->default('Transfer');
            $table->string('bukti_transfer', 255);
            $table->string('status', 30)->default('Menunggu Verifikasi')->index();
            $table->string('catatan', 255)->nullable();
            $table->integer('id_user_pencatat')->nullable();
            $table->integer('id_user_verifikator')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->integer('id_transaksi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_tabungan');
    }
};
