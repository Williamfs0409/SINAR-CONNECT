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

        if (Schema::hasTable('tagihan')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `tagihan` (
          `id_tagihan` int NOT NULL AUTO_INCREMENT,
          `id_rombel` int NOT NULL,
          `id_jenis_biaya` int NOT NULL,
          `periode` date DEFAULT NULL,
          `id_kegiatan` int DEFAULT NULL,
          `nominal` decimal(12,2) NOT NULL,
          `diskon` decimal(12,2) NOT NULL DEFAULT '0.00',
          `total_tagihan` decimal(12,2) GENERATED ALWAYS AS ((`nominal` - `diskon`)) STORED,
          `jatuh_tempo` date NOT NULL,
          `kunci` varchar(40) GENERATED ALWAYS AS (concat(`id_jenis_biaya`,_utf8mb4'|',ifnull(((year(`periode`) * 100) + month(`periode`)),_utf8mb4''),_utf8mb4'|',ifnull(`id_kegiatan`,_utf8mb4''))) STORED,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_tagihan`),
          UNIQUE KEY `uq_tagihan` (`id_rombel`,`kunci`),
          KEY `idx_tagihan_tempo` (`jatuh_tempo`),
          KEY `fk_tagihan_jenis` (`id_jenis_biaya`),
          KEY `fk_tagihan_kegiatan` (`id_kegiatan`),
          CONSTRAINT `fk_tagihan_jenis` FOREIGN KEY (`id_jenis_biaya`) REFERENCES `jenis_biaya` (`id_jenis_biaya`) ON DELETE RESTRICT ON UPDATE RESTRICT,
          CONSTRAINT `fk_tagihan_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE RESTRICT ON UPDATE RESTRICT,
          CONSTRAINT `fk_tagihan_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('tagihan');
    }
};
