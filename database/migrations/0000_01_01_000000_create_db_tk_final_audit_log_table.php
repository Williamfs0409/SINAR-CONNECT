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

        $this->assertEmptyDatabase();

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE `audit_log` (
          `id_log` bigint NOT NULL AUTO_INCREMENT,
          `tabel` varchar(64) NOT NULL,
          `id_record` varchar(64) NOT NULL,
          `aksi` enum('INSERT','UPDATE','DELETE') NOT NULL,
          `data_lama` json DEFAULT NULL,
          `data_baru` json DEFAULT NULL,
          `db_user` varchar(128) DEFAULT NULL,
          `app_user_id` int DEFAULT NULL,
          `waktu` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
          PRIMARY KEY (`id_log`),
          KEY `idx_audit_record` (`tabel`,`id_record`),
          KEY `idx_audit_waktu` (`waktu`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::dropIfExists('audit_log');
    }

    private function assertEmptyDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        $existingTables = DB::select(
            'SELECT table_name FROM information_schema.tables '
            .'WHERE table_schema = ? AND table_name <> ? LIMIT 1',
            [$database, 'migrations']
        );
        $existingRoutines = DB::select(
            'SELECT routine_name FROM information_schema.routines '
            .'WHERE routine_schema = ? LIMIT 1',
            [$database]
        );
        $existingTriggers = DB::select(
            'SELECT trigger_name FROM information_schema.triggers '
            .'WHERE trigger_schema = ? LIMIT 1',
            [$database]
        );

        if ($existingTables !== [] || $existingRoutines !== [] || $existingTriggers !== []) {
            throw new RuntimeException(
                'The db_tk_final baseline migrations require an empty MySQL schema (except for Laravel migrations).'
            );
        }
    }
};
