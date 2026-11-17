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

        if (Schema::hasTable('siswa')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `siswa` (
          `id_siswa` int NOT NULL AUTO_INCREMENT,
          `id_keluarga` int NOT NULL,
          `alamat_domisili` text,
          `no_induk` varchar(30) DEFAULT NULL,
          `nik` varchar(16) DEFAULT NULL,
          `nisn` varchar(10) DEFAULT NULL,
          `nama_lengkap` varchar(100) NOT NULL,
          `nama_panggilan` varchar(100) DEFAULT NULL,
          `tempat_lahir` varchar(100) DEFAULT NULL,
          `tanggal_lahir` date DEFAULT NULL,
          `jenis_kelamin` enum('Laki-Laki','Perempuan') NOT NULL,
          `agama` varchar(20) NOT NULL DEFAULT 'Islam',
          `anak_ke` tinyint DEFAULT NULL,
          `kewarganegaraan` varchar(20) NOT NULL DEFAULT 'WNI',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_siswa`),
          UNIQUE KEY `uq_siswa_induk` (`no_induk`),
          UNIQUE KEY `uq_siswa_nik` (`nik`),
          UNIQUE KEY `uq_siswa_nisn` (`nisn`),
          KEY `idx_siswa_nama` (`nama_lengkap`),
          KEY `idx_siswa_keluarga` (`id_keluarga`),
          CONSTRAINT `fk_siswa_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('siswa');
    }
};
