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

        if (Schema::hasTable('dokumentasi_kegiatan')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `dokumentasi_kegiatan` (
          `id_dokumentasi` int NOT NULL AUTO_INCREMENT,
          `id_kegiatan` int NOT NULL,
          `tahap` enum('Sebelum','Selama','Sesudah') NOT NULL DEFAULT 'Selama',
          `tipe` enum('Foto','Video','Dokumen') NOT NULL DEFAULT 'Foto',
          `file_path` varchar(255) NOT NULL,
          `keterangan` varchar(255) DEFAULT NULL,
          `id_user_upload` int DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_dokumentasi`),
          KEY `idx_dok_kegiatan` (`id_kegiatan`,`tahap`),
          KEY `fk_dok_user` (`id_user_upload`),
          CONSTRAINT `fk_dok_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_dok_user` FOREIGN KEY (`id_user_upload`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('dokumentasi_kegiatan');
    }
};
