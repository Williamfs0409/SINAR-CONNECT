<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_ttab_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_ttab_bd` BEFORE DELETE ON `transaksi_tabungan` FOR EACH ROW BEGIN
          IF COALESCE(@allow_delete_savings, 0) <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh dihapus, buat transaksi koreksi';
          END IF;
        END
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_ttab_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_ttab_bd` BEFORE DELETE ON `transaksi_tabungan` FOR EACH ROW BEGIN
          SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh dihapus, buat transaksi koreksi';
        END
        DB_TK_SQL);
    }
};
