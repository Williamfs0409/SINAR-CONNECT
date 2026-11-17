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

        if (Schema::hasTable('rombel_siswa')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `rombel_siswa` (
          `id_rombel` int NOT NULL AUTO_INCREMENT,
          `id_siswa` int NOT NULL,
          `id_kelas` int NOT NULL,
          `status` enum('Aktif','Selesai','Pindah','Keluar') NOT NULL DEFAULT 'Aktif',
          `tanggal_masuk` date DEFAULT NULL,
          `tanggal_keluar` date DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_rombel`),
          UNIQUE KEY `uq_rombel` (`id_siswa`,`id_kelas`),
          KEY `idx_rombel_kelas_status` (`id_kelas`,`status`),
          CONSTRAINT `fk_rombel_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_rombel_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('rombel_siswa');
    }
};
