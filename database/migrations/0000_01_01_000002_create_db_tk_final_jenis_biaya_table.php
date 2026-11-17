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

        if (Schema::hasTable('jenis_biaya')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `jenis_biaya` (
          `id_jenis_biaya` int NOT NULL AUTO_INCREMENT,
          `nama` varchar(60) NOT NULL,
          `kategori` enum('Bulanan','Pendaftaran','Kegiatan') NOT NULL,
          PRIMARY KEY (`id_jenis_biaya`),
          UNIQUE KEY `uq_jenis_biaya` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('jenis_biaya');
    }
};
