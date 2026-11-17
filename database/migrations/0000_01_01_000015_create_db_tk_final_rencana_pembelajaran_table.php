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

        if (Schema::hasTable('rencana_pembelajaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `rencana_pembelajaran` (
          `id_rencana` int NOT NULL AUTO_INCREMENT,
          `id_kelas` int NOT NULL,
          `id_guru` int NOT NULL,
          `tanggal` date NOT NULL,
          `tema` varchar(150) NOT NULL,
          `subtema` varchar(150) DEFAULT NULL,
          `tujuan` text,
          `kegiatan` text,
          `alat_bahan` text,
          `lampiran` varchar(255) DEFAULT NULL,
          `status` enum('Draft','Final') NOT NULL DEFAULT 'Draft',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_rencana`),
          KEY `idx_rencana` (`id_kelas`,`tanggal`),
          KEY `fk_rencana_guru` (`id_guru`),
          CONSTRAINT `fk_rencana_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_rencana_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('rencana_pembelajaran');
    }
};
