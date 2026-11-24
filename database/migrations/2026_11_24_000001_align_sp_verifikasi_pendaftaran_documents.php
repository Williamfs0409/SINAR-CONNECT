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

        DB::statement('DROP PROCEDURE IF EXISTS `sp_verifikasi_pendaftaran`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_verifikasi_pendaftaran`(
          IN `p_id_pendaftaran` INT,
          IN `p_setuju` TINYINT,
          IN `p_id_user` INT,
          IN `p_catatan` VARCHAR(255)
        )
        BEGIN
          DECLARE v_status VARCHAR(20);
          DECLARE v_valid INT DEFAULT 0;
          DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
          START TRANSACTION;
            SELECT status INTO v_status FROM pendaftaran
            WHERE id_pendaftaran = p_id_pendaftaran FOR UPDATE;
            IF v_status IS NULL THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran tidak ditemukan';
            END IF;
            IF v_status <> 'Diajukan' THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pendaftaran berstatus Diajukan yang dapat diverifikasi';
            END IF;
            IF p_setuju = 1 THEN
              SELECT COUNT(DISTINCT jenis) INTO v_valid FROM dokumen_pendaftaran
              WHERE id_pendaftaran = p_id_pendaftaran AND status_verifikasi = 'Valid'
                AND jenis IN (
                  'Formulir Pendaftaran', 'Akta Kelahiran', 'Kartu Keluarga', 'KTP Ibu', 'KTP Bapak'
                );
              IF v_valid < 5 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lima berkas wajib pendaftaran belum lengkap dan valid';
              END IF;
            END IF;
            UPDATE pendaftaran
            SET status = IF(p_setuju = 1, 'Diverifikasi', 'Ditolak'),
                catatan_verifikasi = p_catatan,
                id_user_verifikator = p_id_user,
                tanggal_verifikasi = NOW()
            WHERE id_pendaftaran = p_id_pendaftaran;
          COMMIT;
        END
        DB_TK_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP PROCEDURE IF EXISTS `sp_verifikasi_pendaftaran`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_verifikasi_pendaftaran`(
          IN `p_id_pendaftaran` INT,
          IN `p_setuju` TINYINT,
          IN `p_id_user` INT,
          IN `p_catatan` VARCHAR(255)
        )
        BEGIN
          DECLARE v_status VARCHAR(20);
          DECLARE v_valid INT DEFAULT 0;
          DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
          START TRANSACTION;
            SELECT status INTO v_status FROM pendaftaran
            WHERE id_pendaftaran = p_id_pendaftaran FOR UPDATE;
            IF v_status IS NULL THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran tidak ditemukan';
            END IF;
            IF v_status <> 'Diajukan' THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pendaftaran berstatus Diajukan yang dapat diverifikasi';
            END IF;
            IF p_setuju = 1 THEN
              SELECT COUNT(DISTINCT jenis) INTO v_valid FROM dokumen_pendaftaran
              WHERE id_pendaftaran = p_id_pendaftaran AND status_verifikasi = 'Valid'
                AND jenis IN (
                  'Formulir', 'Akta Kelahiran', 'Kartu Keluarga', 'KTP Ayah',
                  'KTP Ibu', 'Pas Foto Ayah', 'Pas Foto Ibu'
                );
              IF v_valid < 7 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tujuh berkas wajib belum lengkap dan valid';
              END IF;
            END IF;
            UPDATE pendaftaran
            SET status = IF(p_setuju = 1, 'Diverifikasi', 'Ditolak'),
                catatan_verifikasi = p_catatan, id_user_verifikator = p_id_user
            WHERE id_pendaftaran = p_id_pendaftaran;
          COMMIT;
        END
        DB_TK_SQL);
    }
};
