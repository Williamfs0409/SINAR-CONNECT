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

        if (Schema::hasTable('kegiatan')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `kegiatan` (
          `id_kegiatan` int NOT NULL AUTO_INCREMENT,
          `nama_kegiatan` varchar(150) NOT NULL,
          `jenis` enum('Dalam Kelas','Luar Kelas','Field Trip') NOT NULL,
          `tanggal` date NOT NULL,
          `lokasi` varchar(150) DEFAULT NULL,
          `deskripsi` text,
          `biaya` decimal(12,2) DEFAULT NULL,
          `seluruh_sekolah` tinyint(1) NOT NULL DEFAULT '0',
          `id_user_pembuat` int DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_kegiatan`),
          KEY `idx_kegiatan_tanggal` (`tanggal`),
          KEY `fk_kegiatan_user` (`id_user_pembuat`),
          CONSTRAINT `fk_kegiatan_user` FOREIGN KEY (`id_user_pembuat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('kegiatan');
    }
};
