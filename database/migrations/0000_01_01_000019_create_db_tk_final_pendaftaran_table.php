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

        if (Schema::hasTable('pendaftaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `pendaftaran` (
          `id_pendaftaran` int NOT NULL AUTO_INCREMENT,
          `id_siswa` int NOT NULL,
          `id_tahun_ajaran` int NOT NULL,
          `kelompok` enum('KB','TK A','TK B') NOT NULL,
          `status` enum('Diajukan','Diverifikasi','Diterima','Ditolak') NOT NULL DEFAULT 'Diajukan',
          `catatan_verifikasi` varchar(255) DEFAULT NULL,
          `id_user_verifikator` int DEFAULT NULL,
          `tanggal_verifikasi` datetime DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_pendaftaran`),
          UNIQUE KEY `uq_pendaftaran` (`id_siswa`,`id_tahun_ajaran`),
          KEY `idx_pendaftaran_ta` (`id_tahun_ajaran`,`kelompok`,`status`),
          KEY `fk_pendaftaran_verif` (`id_user_verifikator`),
          CONSTRAINT `fk_pendaftaran_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_pendaftaran_ta` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE,
          CONSTRAINT `fk_pendaftaran_verif` FOREIGN KEY (`id_user_verifikator`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('pendaftaran');
    }
};
