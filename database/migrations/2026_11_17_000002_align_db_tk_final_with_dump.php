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

        foreach (['kegiatan', 'rencana_pembelajaran', 'rombel_siswa', 'tugas', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("The db_tk_final schema is missing the required table [{$table}].");
            }
        }

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TABLE IF NOT EXISTS `diskon` (
          `id_diskon` int NOT NULL AUTO_INCREMENT,
          `nama` varchar(60) NOT NULL,
          `tipe` enum('Persen','Nominal') NOT NULL,
          `nilai` decimal(12,2) NOT NULL,
          `kategori_biaya` enum('Bulanan','Pendaftaran','Kegiatan') NOT NULL DEFAULT 'Bulanan',
          PRIMARY KEY (`id_diskon`),
          UNIQUE KEY `uq_diskon_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        DB_TK_SQL);

        DB::table('diskon')->insertOrIgnore([
            ['id_diskon' => 1, 'nama' => 'Anak Guru', 'tipe' => 'Persen', 'nilai' => 50, 'kategori_biaya' => 'Bulanan'],
            ['id_diskon' => 2, 'nama' => 'Kurang Mampu', 'tipe' => 'Nominal', 'nilai' => 100000, 'kategori_biaya' => 'Bulanan'],
        ]);

        if (! Schema::hasColumn('kegiatan', 'tanggal_selesai')) {
            DB::statement('ALTER TABLE `kegiatan` ADD `tanggal_selesai` DATE NULL AFTER `tanggal`');
        }
        DB::statement(
            "ALTER TABLE `kegiatan` MODIFY `jenis` ENUM('Dalam Kelas','Luar Kelas','Field Trip','Acara','Evaluasi','Libur') NOT NULL"
        );

        if (! Schema::hasColumn('rencana_pembelajaran', 'jenis')) {
            DB::statement(
                "ALTER TABLE `rencana_pembelajaran` ADD `jenis` ENUM('RPPM','RPPH','Modul Ajar') NOT NULL DEFAULT 'RPPH' AFTER `id_guru`"
            );
        }
        if (! Schema::hasColumn('rencana_pembelajaran', 'tanggal_selesai')) {
            DB::statement('ALTER TABLE `rencana_pembelajaran` ADD `tanggal_selesai` DATE NULL AFTER `tanggal`');
        }

        if (! Schema::hasColumn('rombel_siswa', 'id_diskon')) {
            DB::statement('ALTER TABLE `rombel_siswa` ADD `id_diskon` INT NULL AFTER `status`');
        }
        if (! $this->hasForeignKey('rombel_siswa', 'fk_rombel_diskon')) {
            DB::statement(
                'ALTER TABLE `rombel_siswa` ADD CONSTRAINT `fk_rombel_diskon` '
                .'FOREIGN KEY (`id_diskon`) REFERENCES `diskon` (`id_diskon`) '
                .'ON DELETE RESTRICT ON UPDATE CASCADE'
            );
        }

        if (! Schema::hasColumn('tugas', 'kategori')) {
            DB::statement('ALTER TABLE `tugas` ADD `kategori` VARCHAR(30) NULL AFTER `judul`');
        }

        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(150) NULL DEFAULT NULL');
        DB::statement('SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci');

        $this->installUpdatedRoutines();
        $this->installUpdatedTriggers();
        $this->installViews();
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (
            DB::table('rombel_siswa')->whereNotNull('id_diskon')->exists()
            || DB::table('kegiatan')->whereNotNull('tanggal_selesai')
                ->orWhereIn('jenis', ['Acara', 'Evaluasi', 'Libur'])->exists()
            || DB::table('rencana_pembelajaran')->whereNotNull('tanggal_selesai')
                ->orWhere('jenis', '<>', 'RPPH')->exists()
            || DB::table('tugas')->whereNotNull('kategori')->exists()
            || DB::table('users')->whereNull('email')->exists()
            || DB::table('diskon')->count() !== 2
            || ! DB::table('diskon')->where('id_diskon', 1)->where('nama', 'Anak Guru')
                ->where('tipe', 'Persen')->where('nilai', 50)->where('kategori_biaya', 'Bulanan')->exists()
            || ! DB::table('diskon')->where('id_diskon', 2)->where('nama', 'Kurang Mampu')
                ->where('tipe', 'Nominal')->where('nilai', 100000)->where('kategori_biaya', 'Bulanan')->exists()
        ) {
            throw new RuntimeException('Cannot roll back the db_tk_final update because newer data would be lost.');
        }

        foreach (['v_diskon_siswa', 'v_kapasitas_kelas', 'v_keluarga_tanpa_akun'] as $view) {
            DB::statement("DROP VIEW IF EXISTS `{$view}`");
        }
        DB::statement('DROP PROCEDURE IF EXISTS `sp_tempatkan_siswa`');
        DB::statement('DROP TRIGGER IF EXISTS `trg_tugas_bi`');
        DB::statement('DROP TRIGGER IF EXISTS `trg_tugas_bu`');

        DB::statement('DROP TRIGGER IF EXISTS `trg_rombel_bi`');
        DB::statement('DROP TRIGGER IF EXISTS `trg_rombel_bu`');
        DB::statement('DROP PROCEDURE IF EXISTS `sp_buat_tagihan_bulanan`');
        DB::statement('DROP PROCEDURE IF EXISTS `sp_validasi_rombel`');
        DB::statement('DROP PROCEDURE IF EXISTS `sp_verifikasi_pendaftaran`');

        $this->restorePreviousRoutines();
        $this->restorePreviousRombelTriggers();
        $this->restorePreviousActivityReportView();

        DB::statement('ALTER TABLE `rombel_siswa` DROP FOREIGN KEY `fk_rombel_diskon`');
        DB::statement('ALTER TABLE `rombel_siswa` DROP COLUMN `id_diskon`');
        DB::statement('ALTER TABLE `tugas` DROP COLUMN `kategori`');
        DB::statement('ALTER TABLE `rencana_pembelajaran` DROP COLUMN `tanggal_selesai`');
        DB::statement('ALTER TABLE `rencana_pembelajaran` DROP COLUMN `jenis`');
        DB::statement('ALTER TABLE `kegiatan` DROP COLUMN `tanggal_selesai`');
        DB::statement(
            "ALTER TABLE `kegiatan` MODIFY `jenis` ENUM('Dalam Kelas','Luar Kelas','Field Trip') NOT NULL"
        );
        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(150) NOT NULL');
        Schema::dropIfExists('diskon');
    }

    private function installUpdatedRoutines(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS `sp_buat_tagihan_bulanan`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_buat_tagihan_bulanan`(
          IN `p_id_tahun_ajaran` INT,
          IN `p_periode` DATE,
          IN `p_jatuh_tempo` DATE
        )
        BEGIN
          DECLARE v_jatuh_tempo DATE;
          DECLARE v_dibuat INT DEFAULT 0;
          DECLARE v_tanpa_tarif INT DEFAULT 0;
          DECLARE v_berdiskon INT DEFAULT 0;
          DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
          IF DAY(p_periode) <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Periode harus tanggal 1 (contoh 2026-11-01)';
          END IF;
          IF NOT EXISTS (
            SELECT 1 FROM tarif tf JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya
            WHERE tf.id_tahun_ajaran = p_id_tahun_ajaran AND jb.kategori = 'Bulanan'
          ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tarif SPP untuk tahun ajaran ini belum diisi';
          END IF;
          SET v_jatuh_tempo = COALESCE(p_jatuh_tempo, DATE_ADD(p_periode, INTERVAL 9 DAY));
          START TRANSACTION;
            INSERT INTO tagihan (id_rombel, id_jenis_biaya, periode, nominal, diskon, jatuh_tempo)
            SELECT r.id_rombel, tf.id_jenis_biaya, p_periode, tf.nominal,
                   CASE WHEN d.id_diskon IS NULL THEN 0
                        WHEN d.tipe = 'Persen' THEN ROUND(tf.nominal * d.nilai / 100, 2)
                        ELSE LEAST(d.nilai, tf.nominal) END,
                   v_jatuh_tempo
            FROM rombel_siswa r
            JOIN kelas k ON k.id_kelas = r.id_kelas
            JOIN tarif tf ON tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok
            JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
            LEFT JOIN diskon d ON d.id_diskon = r.id_diskon AND d.kategori_biaya = 'Bulanan'
            WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
              AND NOT EXISTS (
                SELECT 1 FROM tagihan x
                WHERE x.id_rombel = r.id_rombel AND x.id_jenis_biaya = tf.id_jenis_biaya AND x.periode = p_periode
              );
            SET v_dibuat = ROW_COUNT();
            SELECT COUNT(*) INTO v_tanpa_tarif
            FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
            WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
              AND NOT EXISTS (
                SELECT 1 FROM tarif tf
                JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
                WHERE tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok
              );
            SELECT COUNT(*) INTO v_berdiskon
            FROM tagihan t
            JOIN rombel_siswa r ON r.id_rombel = t.id_rombel
            JOIN kelas k ON k.id_kelas = r.id_kelas
            WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND t.periode = p_periode AND t.diskon > 0;
          COMMIT;
          SELECT v_dibuat AS tagihan_dibuat, v_berdiskon AS tagihan_berdiskon,
                 v_tanpa_tarif AS siswa_tanpa_tarif, v_jatuh_tempo AS jatuh_tempo;
        END
        DB_TK_SQL);

        DB::statement('DROP PROCEDURE IF EXISTS `sp_validasi_rombel`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_validasi_rombel`(
          IN `p_id_siswa` INT,
          IN `p_id_kelas` INT,
          IN `p_abaikan_rombel` INT,
          IN `p_status` VARCHAR(10)
        )
        BEGIN
          DECLARE v_ta INT;
          DECLARE v_kapasitas TINYINT;
          DECLARE v_nama VARCHAR(50);
          DECLARE v_isi INT DEFAULT 0;
          SELECT id_tahun_ajaran, kapasitas, nama_kelas
            INTO v_ta, v_kapasitas, v_nama
          FROM kelas WHERE id_kelas = p_id_kelas;
          IF EXISTS (
            SELECT 1 FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
            WHERE r.id_siswa = p_id_siswa AND k.id_tahun_ajaran = v_ta
              AND r.id_rombel <> COALESCE(p_abaikan_rombel, 0)
          ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa sudah punya kelas pada tahun ajaran tersebut';
          END IF;
          IF p_status = 'Aktif' AND v_kapasitas IS NOT NULL AND v_nama <> 'Belum Ditentukan' THEN
            SELECT COUNT(*) INTO v_isi FROM rombel_siswa
            WHERE id_kelas = p_id_kelas AND status = 'Aktif'
              AND id_rombel <> COALESCE(p_abaikan_rombel, 0);
            IF v_isi >= v_kapasitas THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kapasitas kelas sudah penuh';
            END IF;
          END IF;
        END
        DB_TK_SQL);

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

        DB::statement('DROP PROCEDURE IF EXISTS `sp_tempatkan_siswa`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_tempatkan_siswa`(IN `p_id_rombel` INT, IN `p_id_kelas_baru` INT)
        BEGIN
          DECLARE v_kelas_lama INT;
          DECLARE v_ta_lama INT;
          DECLARE v_ta_baru INT;
          DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
          START TRANSACTION;
            SELECT id_kelas INTO v_kelas_lama FROM rombel_siswa
            WHERE id_rombel = p_id_rombel FOR UPDATE;
            IF v_kelas_lama IS NULL THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penempatan siswa tidak ditemukan';
            END IF;
            SELECT id_tahun_ajaran INTO v_ta_lama FROM kelas WHERE id_kelas = v_kelas_lama;
            SELECT id_tahun_ajaran INTO v_ta_baru FROM kelas WHERE id_kelas = p_id_kelas_baru;
            IF v_ta_baru IS NULL OR v_ta_baru <> v_ta_lama THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kelas tujuan harus berada pada tahun ajaran yang sama';
            END IF;
            UPDATE rombel_siswa SET id_kelas = p_id_kelas_baru WHERE id_rombel = p_id_rombel;
          COMMIT;
          SELECT p_id_rombel AS id_rombel, p_id_kelas_baru AS id_kelas_baru;
        END
        DB_TK_SQL);
    }

    private function installUpdatedTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS `trg_rombel_bi`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_rombel_bi` BEFORE INSERT ON `rombel_siswa` FOR EACH ROW
        BEGIN
          CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, NULL, NEW.status);
        END
        DB_TK_SQL);

        DB::statement('DROP TRIGGER IF EXISTS `trg_rombel_bu`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_rombel_bu` BEFORE UPDATE ON `rombel_siswa` FOR EACH ROW
        BEGIN
          IF NEW.id_siswa <> OLD.id_siswa OR NEW.id_kelas <> OLD.id_kelas
             OR (NEW.status = 'Aktif' AND OLD.status <> 'Aktif') THEN
            CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, OLD.id_rombel, NEW.status);
          END IF;
        END
        DB_TK_SQL);

        DB::statement('DROP TRIGGER IF EXISTS `trg_tugas_bi`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_tugas_bi` BEFORE INSERT ON `tugas` FOR EACH ROW
        BEGIN
          IF (SELECT kelompok FROM kelas WHERE id_kelas = NEW.id_kelas) = 'KB' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kelompok Bermain (PG) tidak diberi tugas';
          END IF;
        END
        DB_TK_SQL);

        DB::statement('DROP TRIGGER IF EXISTS `trg_tugas_bu`');
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_tugas_bu` BEFORE UPDATE ON `tugas` FOR EACH ROW
        BEGIN
          IF NEW.id_kelas <> OLD.id_kelas
             AND (SELECT kelompok FROM kelas WHERE id_kelas = NEW.id_kelas) = 'KB' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kelompok Bermain (PG) tidak diberi tugas';
          END IF;
        END
        DB_TK_SQL);
    }

    private function installViews(): void
    {
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE OR REPLACE VIEW `v_laporan_kegiatan` AS
        SELECT k.id_kegiatan, k.nama_kegiatan, k.jenis, k.tanggal, k.tanggal_selesai,
               k.lokasi, k.biaya, k.seluruh_sekolah,
               (SELECT GROUP_CONCAT(kl.nama_kelas ORDER BY kl.nama_kelas SEPARATOR ', ')
                FROM kegiatan_kelas kk JOIN kelas kl ON kl.id_kelas = kk.id_kelas
                WHERE kk.id_kegiatan = k.id_kegiatan) AS kelas_terlibat,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Sebelum') AS laporan_sebelum,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Selama') AS laporan_selama,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Sesudah') AS laporan_sesudah,
               (SELECT COUNT(*) FROM tagihan t
                WHERE t.id_kegiatan = k.id_kegiatan) AS tagihan_terbit
        FROM kegiatan k
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE OR REPLACE VIEW `v_diskon_siswa` AS
        SELECT r.id_rombel, s.id_siswa, s.nama_lengkap, kl.nama_kelas,
               d.nama AS diskon, d.tipe, d.nilai
        FROM rombel_siswa r
        JOIN diskon d ON d.id_diskon = r.id_diskon
        JOIN siswa s ON s.id_siswa = r.id_siswa
        JOIN kelas kl ON kl.id_kelas = r.id_kelas
        WHERE r.status = 'Aktif'
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE OR REPLACE VIEW `v_kapasitas_kelas` AS
        SELECT x.id_kelas, x.tahun_ajaran, x.kelompok, x.nama_kelas,
               x.kapasitas, x.siswa_aktif, x.kapasitas - x.siswa_aktif AS sisa_kursi,
               CASE
                 WHEN x.kapasitas IS NULL THEN 'Kapasitas belum diisi'
                 WHEN x.siswa_aktif > x.kapasitas THEN 'Melebihi kapasitas'
                 WHEN x.siswa_aktif = x.kapasitas THEN 'Penuh'
                 ELSE 'Tersedia'
               END AS status
        FROM (
          SELECT kl.id_kelas, ta.nama AS tahun_ajaran, kl.kelompok, kl.nama_kelas,
                 kl.kapasitas,
                 (SELECT COUNT(*) FROM rombel_siswa r
                  WHERE r.id_kelas = kl.id_kelas AND r.status = 'Aktif') AS siswa_aktif
          FROM kelas kl JOIN tahun_ajaran ta ON ta.id_tahun_ajaran = kl.id_tahun_ajaran
        ) AS x
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE OR REPLACE VIEW `v_keluarga_tanpa_akun` AS
        SELECT f.id_keluarga, f.nama_ayah, f.nama_ibu, f.no_hp_utama,
               (SELECT GROUP_CONCAT(s.nama_lengkap ORDER BY s.nama_lengkap SEPARATOR ', ')
                FROM siswa s JOIN rombel_siswa r
                  ON r.id_siswa = s.id_siswa AND r.status = 'Aktif'
                WHERE s.id_keluarga = f.id_keluarga) AS anak_aktif
        FROM v_keluarga_lengkap f
        WHERE f.id_user IS NULL
          AND EXISTS (
            SELECT 1 FROM siswa s JOIN rombel_siswa r
              ON r.id_siswa = s.id_siswa AND r.status = 'Aktif'
            WHERE s.id_keluarga = f.id_keluarga
          )
        DB_TK_SQL);
    }

    private function restorePreviousActivityReportView(): void
    {
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE OR REPLACE VIEW `v_laporan_kegiatan` AS
        SELECT k.id_kegiatan, k.nama_kegiatan, k.jenis, k.tanggal, k.lokasi, k.biaya,
               k.seluruh_sekolah,
               (SELECT GROUP_CONCAT(kl.nama_kelas ORDER BY kl.nama_kelas SEPARATOR ', ')
                FROM kegiatan_kelas kk JOIN kelas kl ON kl.id_kelas = kk.id_kelas
                WHERE kk.id_kegiatan = k.id_kegiatan) AS kelas_terlibat,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Sebelum') AS laporan_sebelum,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Selama') AS laporan_selama,
               (SELECT COUNT(*) FROM dokumentasi_kegiatan d
                WHERE d.id_kegiatan = k.id_kegiatan AND d.tahap = 'Sesudah') AS laporan_sesudah,
               (SELECT COUNT(*) FROM tagihan t
                WHERE t.id_kegiatan = k.id_kegiatan) AS tagihan_terbit
        FROM kegiatan k
        DB_TK_SQL);
    }

    private function restorePreviousRoutines(): void
    {
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_buat_tagihan_bulanan`(IN `p_id_tahun_ajaran` INT, IN `p_periode` DATE, IN `p_jatuh_tempo` DATE)
        BEGIN
          DECLARE v_dibuat INT DEFAULT 0;
          DECLARE v_tanpa_tarif INT DEFAULT 0;
          DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
          IF DAY(p_periode) <> 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Periode harus tanggal 1 (contoh 2026-11-01)';
          END IF;
          IF NOT EXISTS (SELECT 1 FROM tarif tf JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya
                         WHERE tf.id_tahun_ajaran = p_id_tahun_ajaran AND jb.kategori = 'Bulanan') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tarif SPP untuk tahun ajaran ini belum diisi';
          END IF;
          START TRANSACTION;
            INSERT INTO tagihan (id_rombel, id_jenis_biaya, periode, nominal, diskon, jatuh_tempo)
            SELECT r.id_rombel, tf.id_jenis_biaya, p_periode, tf.nominal, 0, p_jatuh_tempo
            FROM rombel_siswa r
            JOIN kelas k ON k.id_kelas = r.id_kelas
            JOIN tarif tf ON tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok
            JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
            WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
              AND NOT EXISTS (SELECT 1 FROM tagihan x WHERE x.id_rombel = r.id_rombel
                              AND x.id_jenis_biaya = tf.id_jenis_biaya AND x.periode = p_periode);
            SET v_dibuat = ROW_COUNT();
            SELECT COUNT(*) INTO v_tanpa_tarif
            FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
            WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
              AND NOT EXISTS (SELECT 1 FROM tarif tf JOIN jenis_biaya jb
                              ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
                              WHERE tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok);
          COMMIT;
          SELECT v_dibuat AS tagihan_dibuat, v_tanpa_tarif AS siswa_tanpa_tarif;
        END
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_validasi_rombel`(IN `p_id_siswa` INT, IN `p_id_kelas` INT, IN `p_abaikan_rombel` INT)
        BEGIN
          DECLARE v_ta INT;
          SELECT id_tahun_ajaran INTO v_ta FROM kelas WHERE id_kelas = p_id_kelas;
          IF EXISTS (SELECT 1 FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
                     WHERE r.id_siswa = p_id_siswa AND k.id_tahun_ajaran = v_ta
                       AND r.id_rombel <> COALESCE(p_abaikan_rombel, 0)) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa sudah punya kelas pada tahun ajaran tersebut';
          END IF;
        END
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE PROCEDURE `sp_verifikasi_pendaftaran`(IN `p_id_pendaftaran` INT, IN `p_setuju` TINYINT, IN `p_id_user` INT, IN `p_catatan` VARCHAR(255))
        BEGIN
          DECLARE v_status VARCHAR(20);
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
            IF p_setuju = 1 AND EXISTS (
              SELECT 1 FROM dokumen_pendaftaran
              WHERE id_pendaftaran = p_id_pendaftaran AND status_verifikasi <> 'Valid'
            ) THEN
              SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada dokumen yang belum valid';
            END IF;
            UPDATE pendaftaran
            SET status = IF(p_setuju = 1, 'Diverifikasi', 'Ditolak'),
                catatan_verifikasi = p_catatan, id_user_verifikator = p_id_user
            WHERE id_pendaftaran = p_id_pendaftaran;
          COMMIT;
        END
        DB_TK_SQL);
    }

    private function restorePreviousRombelTriggers(): void
    {
        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_rombel_bi` BEFORE INSERT ON `rombel_siswa` FOR EACH ROW
        BEGIN
          CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, NULL);
        END
        DB_TK_SQL);

        DB::unprepared(<<<'DB_TK_SQL'
        CREATE TRIGGER `trg_rombel_bu` BEFORE UPDATE ON `rombel_siswa` FOR EACH ROW
        BEGIN
          IF NEW.id_siswa <> OLD.id_siswa OR NEW.id_kelas <> OLD.id_kelas THEN
            CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, OLD.id_rombel);
          END IF;
        END
        DB_TK_SQL);
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS '
            .'WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [DB::connection()->getDatabaseName(), $table, $constraint],
        ) !== null;
    }
};
