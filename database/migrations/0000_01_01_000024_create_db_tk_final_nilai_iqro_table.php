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

        if (Schema::hasTable('nilai_iqro')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `nilai_iqro` (
          `id_nilai` int NOT NULL AUTO_INCREMENT,
          `id_rombel` int NOT NULL,
          `id_indikator` int NOT NULL,
          `semester` enum('Ganjil','Genap') NOT NULL,
          `skala` enum('BB','MB','BSH','BSB') NOT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_nilai`),
          UNIQUE KEY `uq_nilai_iqro` (`id_rombel`,`id_indikator`,`semester`),
          KEY `fk_niqro_indikator` (`id_indikator`),
          CONSTRAINT `fk_niqro_indikator` FOREIGN KEY (`id_indikator`) REFERENCES `indikator_iqro` (`id_indikator`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_niqro_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('nilai_iqro');
    }
};
