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

        if (Schema::hasTable('indikator_iqro')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `indikator_iqro` (
          `id_indikator` int NOT NULL AUTO_INCREMENT,
          `kelompok` enum('KB','TK A','TK B') NOT NULL,
          `kategori` varchar(60) DEFAULT NULL,
          `nama` varchar(120) NOT NULL,
          `urutan` smallint NOT NULL,
          PRIMARY KEY (`id_indikator`),
          UNIQUE KEY `uq_indikator_urutan` (`kelompok`,`urutan`),
          UNIQUE KEY `uq_indikator_nama` (`kelompok`,`kategori`,`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('indikator_iqro');
    }
};
