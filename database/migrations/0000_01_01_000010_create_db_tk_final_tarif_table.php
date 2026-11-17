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

        if (Schema::hasTable('tarif')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `tarif` (
          `id_tarif` int NOT NULL AUTO_INCREMENT,
          `id_tahun_ajaran` int NOT NULL,
          `kelompok` enum('KB','TK A','TK B') NOT NULL,
          `id_jenis_biaya` int NOT NULL,
          `nominal` decimal(12,2) NOT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_tarif`),
          UNIQUE KEY `uq_tarif` (`id_tahun_ajaran`,`kelompok`,`id_jenis_biaya`),
          KEY `fk_tarif_jenis` (`id_jenis_biaya`),
          CONSTRAINT `fk_tarif_jenis` FOREIGN KEY (`id_jenis_biaya`) REFERENCES `jenis_biaya` (`id_jenis_biaya`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_tarif_ta` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('tarif');
    }
};
