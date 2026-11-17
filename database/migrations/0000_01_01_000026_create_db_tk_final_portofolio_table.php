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

        if (Schema::hasTable('portofolio')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `portofolio` (
          `id_portofolio` int NOT NULL AUTO_INCREMENT,
          `id_rombel` int NOT NULL,
          `tanggal` date NOT NULL,
          `foto` varchar(255) NOT NULL,
          `narasi` text NOT NULL,
          `id_guru` int DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_portofolio`),
          KEY `idx_portofolio` (`id_rombel`,`tanggal`),
          KEY `fk_porto_guru` (`id_guru`),
          CONSTRAINT `fk_porto_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_porto_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('portofolio');
    }
};
