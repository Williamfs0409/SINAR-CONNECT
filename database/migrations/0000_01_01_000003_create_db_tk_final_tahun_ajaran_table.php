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

        if (Schema::hasTable('tahun_ajaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `tahun_ajaran` (
          `id_tahun_ajaran` int NOT NULL AUTO_INCREMENT,
          `nama` varchar(9) NOT NULL,
          `tahun_mulai` smallint NOT NULL,
          `is_aktif` tinyint(1) NOT NULL DEFAULT '0',
          `aktif_unik` tinyint GENERATED ALWAYS AS (if((`is_aktif` = 1),1,NULL)) VIRTUAL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_tahun_ajaran`),
          UNIQUE KEY `uq_ta_nama` (`nama`),
          UNIQUE KEY `uq_ta_aktif` (`aktif_unik`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('tahun_ajaran');
    }
};
