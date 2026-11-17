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

        if (Schema::hasTable('pembayaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `pembayaran` (
          `id_pembayaran` int NOT NULL AUTO_INCREMENT,
          `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `metode` enum('Tunai','Transfer') NOT NULL,
          `bukti_transfer` varchar(255) DEFAULT NULL,
          `status` enum('Menunggu Verifikasi','Terverifikasi','Ditolak','Dibatalkan') NOT NULL DEFAULT 'Menunggu Verifikasi',
          `catatan` varchar(255) DEFAULT NULL,
          `id_user_pencatat` int DEFAULT NULL,
          `id_user_verifikator` int DEFAULT NULL,
          `tanggal_verifikasi` datetime DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_pembayaran`),
          KEY `idx_bayar_tanggal` (`tanggal_bayar`),
          KEY `fk_bayar_pencatat` (`id_user_pencatat`),
          KEY `fk_bayar_verifikator` (`id_user_verifikator`),
          CONSTRAINT `fk_bayar_pencatat` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_bayar_verifikator` FOREIGN KEY (`id_user_verifikator`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('pembayaran');
    }
};
