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

        if (Schema::hasTable('kontak_keluarga')) {
            throw new RuntimeException('The db_tk_final baseline migrations require an empty MySQL database.');
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `kontak_keluarga` (
          `id_kontak` int NOT NULL AUTO_INCREMENT,
          `id_keluarga` int NOT NULL,
          `nomor` varchar(16) NOT NULL,
          `urutan` tinyint NOT NULL DEFAULT '1',
          PRIMARY KEY (`id_kontak`),
          UNIQUE KEY `uq_kontak_nomor` (`id_keluarga`,`nomor`),
          UNIQUE KEY `uq_kontak_urutan` (`id_keluarga`,`urutan`),
          CONSTRAINT `fk_kontak_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('kontak_keluarga');
    }
};
