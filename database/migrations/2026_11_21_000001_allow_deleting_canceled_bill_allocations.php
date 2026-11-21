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

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_alokasi_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_alokasi_bd` BEFORE DELETE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
          DECLARE v_status VARCHAR(30);
          SELECT status INTO v_status FROM pembayaran WHERE id_pembayaran = OLD.id_pembayaran;
          IF v_status IS NULL OR v_status NOT IN ('Dibatalkan', 'Ditolak') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran yang aktif tidak boleh dihapus';
          END IF;
        END
        DB_TK_SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_pembayaran_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_pembayaran_bd` BEFORE DELETE ON `pembayaran` FOR EACH ROW BEGIN
          IF OLD.status NOT IN ('Dibatalkan', 'Ditolak') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran yang aktif tidak boleh dihapus; batalkan kuitansi terlebih dahulu';
          END IF;
        END
        DB_TK_SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_tagihan_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_tagihan_bd` BEFORE DELETE ON `tagihan` FOR EACH ROW BEGIN
          IF EXISTS (
            SELECT 1
              FROM alokasi_pembayaran a
              JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
             WHERE a.id_tagihan = OLD.id_tagihan
               AND p.status NOT IN ('Dibatalkan', 'Ditolak')
          ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan yang sudah memiliki pembayaran aktif tidak boleh dihapus';
          END IF;
        END
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_alokasi_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_alokasi_bd` BEFORE DELETE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
          SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran tidak boleh dihapus';
        END
        DB_TK_SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_pembayaran_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_pembayaran_bd` BEFORE DELETE ON `pembayaran` FOR EACH ROW BEGIN
          SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran tidak boleh dihapus';
        END
        DB_TK_SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_tagihan_bd`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_tagihan_bd` BEFORE DELETE ON `tagihan` FOR EACH ROW BEGIN
          IF EXISTS (SELECT 1 FROM alokasi_pembayaran WHERE id_tagihan = OLD.id_tagihan) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan yang sudah memiliki pembayaran tidak boleh dihapus';
          END IF;
        END
        DB_TK_SQL);
    }
};
