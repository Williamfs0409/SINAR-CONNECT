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

        if (Schema::hasTable('wali')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `wali` (
          `id_wali` int NOT NULL AUTO_INCREMENT,
          `id_keluarga` int NOT NULL,
          `hubungan` enum('Ayah','Ibu','Wali') NOT NULL,
          `gelar_depan` varchar(20) DEFAULT NULL,
          `nama_lengkap` varchar(100) NOT NULL,
          `gelar_belakang` varchar(50) DEFAULT NULL,
          `tempat_lahir` varchar(100) DEFAULT NULL,
          `tanggal_lahir` date DEFAULT NULL,
          `pendidikan` varchar(30) DEFAULT NULL,
          `pekerjaan` varchar(100) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_wali`),
          UNIQUE KEY `uq_wali_hubungan` (`id_keluarga`,`hubungan`),
          KEY `idx_wali_nama` (`nama_lengkap`),
          CONSTRAINT `fk_wali_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('wali');
    }
};
