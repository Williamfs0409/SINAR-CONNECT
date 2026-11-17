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

        if (Schema::hasTable('dokumen_pendaftaran')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `dokumen_pendaftaran` (
          `id_dokumen` int NOT NULL AUTO_INCREMENT,
          `id_pendaftaran` int NOT NULL,
          `jenis` varchar(50) NOT NULL,
          `file_path` varchar(255) NOT NULL,
          `status_verifikasi` enum('Menunggu','Valid','Tidak Valid') NOT NULL DEFAULT 'Menunggu',
          `catatan` varchar(255) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_dokumen`),
          UNIQUE KEY `uq_dokumen` (`id_pendaftaran`,`jenis`),
          CONSTRAINT `fk_dokumen_pendaftaran` FOREIGN KEY (`id_pendaftaran`) REFERENCES `pendaftaran` (`id_pendaftaran`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('dokumen_pendaftaran');
    }
};
