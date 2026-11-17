<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('alokasi_pembayaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `alokasi_pembayaran` (
          `id_alokasi` int NOT NULL AUTO_INCREMENT,
          `id_pembayaran` int NOT NULL,
          `id_tagihan` int NOT NULL,
          `jumlah` decimal(12,2) NOT NULL,
          PRIMARY KEY (`id_alokasi`),
          UNIQUE KEY `uq_alokasi` (`id_pembayaran`,`id_tagihan`),
          KEY `idx_alokasi_tagihan` (`id_tagihan`),
          CONSTRAINT `fk_alokasi_bayar` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_alokasi_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('alokasi_pembayaran');
    }
};
