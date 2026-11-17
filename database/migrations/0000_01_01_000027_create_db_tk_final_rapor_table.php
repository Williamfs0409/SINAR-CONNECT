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

        if (Schema::hasTable('rapor')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `rapor` (
          `id_rapor` int NOT NULL AUTO_INCREMENT,
          `id_rombel` int NOT NULL,
          `semester` enum('Ganjil','Genap') NOT NULL,
          `tanggal_rapor` date DEFAULT NULL,
          `tinggi_badan` decimal(5,1) DEFAULT NULL,
          `berat_badan` decimal(5,1) DEFAULT NULL,
          `narasi_agama_budi_pekerti` text,
          `narasi_jati_diri` text,
          `narasi_literasi_steam` text,
          `narasi_p5` text,
          `refleksi_orang_tua` text,
          `id_guru_kelas` int DEFAULT NULL,
          `id_guru_iqro` int DEFAULT NULL,
          `status` enum('Draft','Final') NOT NULL DEFAULT 'Draft',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id_rapor`),
          UNIQUE KEY `uq_rapor` (`id_rombel`,`semester`),
          KEY `fk_rapor_kelas` (`id_guru_kelas`),
          KEY `fk_rapor_iqro` (`id_guru_iqro`),
          CONSTRAINT `fk_rapor_iqro` FOREIGN KEY (`id_guru_iqro`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_rapor_kelas` FOREIGN KEY (`id_guru_kelas`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_rapor_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('rapor');
    }
};
