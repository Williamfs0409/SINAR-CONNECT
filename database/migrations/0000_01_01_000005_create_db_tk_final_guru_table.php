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

        if (Schema::hasTable('guru')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `guru` (
          `id_guru` int NOT NULL AUTO_INCREMENT,
          `id_user` int DEFAULT NULL,
          `nuptk` varchar(16) DEFAULT NULL,
          `nik` varchar(16) DEFAULT NULL,
          `gelar_depan` varchar(20) DEFAULT NULL,
          `nama_lengkap` varchar(100) NOT NULL,
          `gelar_belakang` varchar(50) DEFAULT NULL,
          `tempat_lahir` varchar(100) DEFAULT NULL,
          `tanggal_lahir` date DEFAULT NULL,
          `jenis_kelamin` enum('Laki-Laki','Perempuan') DEFAULT NULL,
          `no_hp` varchar(16) DEFAULT NULL,
          `alamat` text,
          `pendidikan` varchar(30) DEFAULT NULL,
          `jabatan` varchar(30) NOT NULL,
          `status_kepegawaian` varchar(20) NOT NULL,
          `tanggal_mulai` date DEFAULT NULL,
          `foto` varchar(255) DEFAULT NULL,
          `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
          `kepsek_unik` tinyint GENERATED ALWAYS AS (if(((`jabatan` = _utf8mb4'Kepala Sekolah') and (`status` = _utf8mb4'Aktif')),1,NULL)) VIRTUAL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_guru`),
          UNIQUE KEY `uq_guru_user` (`id_user`),
          UNIQUE KEY `uq_guru_nuptk` (`nuptk`),
          UNIQUE KEY `uq_guru_nik` (`nik`),
          UNIQUE KEY `uq_guru_kepsek` (`kepsek_unik`),
          CONSTRAINT `fk_guru_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('guru');
    }
};
