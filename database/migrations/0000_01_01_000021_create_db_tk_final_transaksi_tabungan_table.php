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

        if (Schema::hasTable('transaksi_tabungan')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `transaksi_tabungan` (
          `id_transaksi` int NOT NULL AUTO_INCREMENT,
          `id_siswa` int NOT NULL,
          `jenis` enum('Setor','Tarik') NOT NULL,
          `jumlah` decimal(14,2) NOT NULL,
          `metode` enum('Tunai','Transfer') NOT NULL DEFAULT 'Tunai',
          `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `keterangan` varchar(255) DEFAULT NULL,
          `id_user_pencatat` int DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_transaksi`),
          KEY `idx_ttab` (`id_siswa`,`tanggal`),
          KEY `fk_ttab_user` (`id_user_pencatat`),
          CONSTRAINT `fk_ttab_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_ttab_user` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('transaksi_tabungan');
    }
};
