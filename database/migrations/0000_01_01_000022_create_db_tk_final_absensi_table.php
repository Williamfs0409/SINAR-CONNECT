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

        if (Schema::hasTable('absensi')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `absensi` (
          `id_absensi` int NOT NULL AUTO_INCREMENT,
          `id_rombel` int NOT NULL,
          `tanggal` date NOT NULL,
          `status` enum('Hadir','Izin','Sakit','Alfa') NOT NULL DEFAULT 'Hadir',
          `keterangan` varchar(255) DEFAULT NULL,
          `id_user_pencatat` int DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_absensi`),
          UNIQUE KEY `uq_absensi` (`id_rombel`,`tanggal`),
          KEY `idx_absensi_tanggal` (`tanggal`),
          KEY `fk_absensi_user` (`id_user_pencatat`),
          CONSTRAINT `fk_absensi_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_absensi_user` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('absensi');
    }
};
