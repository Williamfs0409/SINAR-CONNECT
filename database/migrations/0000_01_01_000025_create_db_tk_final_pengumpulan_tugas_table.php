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

        if (Schema::hasTable('pengumpulan_tugas')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `pengumpulan_tugas` (
          `id_pengumpulan` int NOT NULL AUTO_INCREMENT,
          `id_tugas` int NOT NULL,
          `id_rombel` int NOT NULL,
          `file_path` varchar(255) DEFAULT NULL,
          `catatan_ortu` varchar(255) DEFAULT NULL,
          `dikumpulkan_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `nilai` enum('BB','MB','BSH','BSB') DEFAULT NULL,
          `umpan_balik` text,
          `id_guru_penilai` int DEFAULT NULL,
          `dinilai_pada` datetime DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_pengumpulan`),
          UNIQUE KEY `uq_pengumpulan` (`id_tugas`,`id_rombel`),
          KEY `fk_pengumpulan_rombel` (`id_rombel`),
          KEY `fk_pengumpulan_guru` (`id_guru_penilai`),
          CONSTRAINT `fk_pengumpulan_guru` FOREIGN KEY (`id_guru_penilai`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_pengumpulan_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_pengumpulan_tugas` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('pengumpulan_tugas');
    }
};
