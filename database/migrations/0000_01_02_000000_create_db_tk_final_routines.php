<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OBJECTS = [
        [
            'type' => 'FUNCTION',
            'name' => 'fn_akhir_semester',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_akhir_semester`(`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS date
                NO SQL
                DETERMINISTIC
            BEGIN
              RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-12-31') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-06-30') AS DATE));
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_awal_semester',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_awal_semester`(`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS date
                NO SQL
                DETERMINISTIC
            BEGIN
              RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-07-01') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-01-01') AS DATE));
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_dibayar_tagihan',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_dibayar_tagihan`(`p_id_tagihan` INT) RETURNS decimal(12,2)
                READS SQL DATA
            BEGIN
              DECLARE v DECIMAL(12,2);
              SELECT COALESCE(SUM(a.jumlah), 0) INTO v
                FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
               WHERE a.id_tagihan = p_id_tagihan AND p.status = 'Terverifikasi';
              RETURN v;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_hitung_usia',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_hitung_usia`(`p_tanggal_lahir` DATE) RETURNS varchar(30) CHARSET utf8mb4
                NO SQL
            BEGIN
              DECLARE v_bulan INT;
              IF p_tanggal_lahir IS NULL THEN RETURN NULL; END IF;
              SET v_bulan = TIMESTAMPDIFF(MONTH, p_tanggal_lahir, CURDATE());
              RETURN CONCAT(FLOOR(v_bulan / 12), ' tahun ', MOD(v_bulan, 12), ' bulan');
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_no_kuitansi',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_no_kuitansi`(`p_id` INT, `p_tanggal` DATETIME) RETURNS varchar(20) CHARSET utf8mb4
                NO SQL
                DETERMINISTIC
            BEGIN
              RETURN CONCAT('KWT-', YEAR(p_tanggal), '-', LPAD(p_id, 5, '0'));
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_no_pendaftaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_no_pendaftaran`(`p_id` INT, `p_tahun_mulai` SMALLINT) RETURNS varchar(20) CHARSET utf8mb4
                NO SQL
                DETERMINISTIC
            BEGIN
              RETURN CONCAT('PPDB-', p_tahun_mulai, '-', LPAD(p_id, 4, '0'));
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_persen_kehadiran',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_persen_kehadiran`(`p_id_siswa` INT, `p_dari` DATE, `p_sampai` DATE) RETURNS decimal(5,2)
                READS SQL DATA
            BEGIN
              DECLARE v DECIMAL(5,2);
              SELECT ROUND(100 * SUM(a.status = 'Hadir') / NULLIF(COUNT(*), 0), 2) INTO v
                FROM absensi a JOIN rombel_siswa r ON r.id_rombel = a.id_rombel
               WHERE r.id_siswa = p_id_siswa AND a.tanggal BETWEEN p_dari AND p_sampai;
              RETURN v;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_saldo_tabungan',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_saldo_tabungan`(`p_id_siswa` INT) RETURNS decimal(14,2)
                READS SQL DATA
            BEGIN
              DECLARE v DECIMAL(14,2);
              SELECT COALESCE(SUM(IF(jenis = 'Setor', jumlah, -jumlah)), 0) INTO v FROM transaksi_tabungan WHERE id_siswa = p_id_siswa;
              RETURN v;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_semester',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_semester`(`p_tanggal` DATE) RETURNS varchar(6) CHARSET utf8mb4
                NO SQL
                DETERMINISTIC
            BEGIN
              RETURN IF(MONTH(p_tanggal) >= 7, 'Ganjil', 'Genap');
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_title_case',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_title_case`(`p_teks` VARCHAR(255)) RETURNS varchar(255) CHARSET utf8mb4
                NO SQL
                DETERMINISTIC
            BEGIN
              DECLARE v_hasil VARCHAR(255) DEFAULT '';
              DECLARE v_sisa  VARCHAR(255);
              DECLARE v_kata  VARCHAR(255);
              DECLARE v_pos   INT;
              IF p_teks IS NULL THEN RETURN NULL; END IF;
              SET v_sisa = TRIM(REGEXP_REPLACE(p_teks, '[[:space:]]+', ' '));
              WHILE CHAR_LENGTH(v_sisa) > 0 DO
                SET v_pos = LOCATE(' ', v_sisa);
                IF v_pos = 0 THEN SET v_kata = v_sisa; SET v_sisa = '';
                ELSE SET v_kata = LEFT(v_sisa, v_pos - 1); SET v_sisa = SUBSTRING(v_sisa, v_pos + 1); END IF;
                SET v_hasil = CONCAT_WS(' ', NULLIF(v_hasil, ''), CONCAT(UPPER(LEFT(v_kata, 1)), LOWER(SUBSTRING(v_kata, 2))));
              END WHILE;
              RETURN v_hasil;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_total_tunggakan_keluarga',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_total_tunggakan_keluarga`(`p_id_keluarga` INT) RETURNS decimal(14,2)
                READS SQL DATA
            BEGIN
              DECLARE v DECIMAL(14,2);
              SELECT COALESCE(SUM(t.total_tagihan - COALESCE(d.dibayar, 0)), 0) INTO v
              FROM tagihan t
              JOIN rombel_siswa r ON r.id_rombel = t.id_rombel
              JOIN siswa s ON s.id_siswa = r.id_siswa
              LEFT JOIN (SELECT a.id_tagihan, SUM(a.jumlah) AS dibayar
                         FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
                         WHERE p.status = 'Terverifikasi' GROUP BY a.id_tagihan) d ON d.id_tagihan = t.id_tagihan
              WHERE s.id_keluarga = p_id_keluarga AND t.jatuh_tempo < CURDATE() AND t.total_tagihan - COALESCE(d.dibayar, 0) > 0;
              RETURN v;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_total_tunggakan_siswa',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_total_tunggakan_siswa`(`p_id_siswa` INT) RETURNS decimal(14,2)
                READS SQL DATA
            BEGIN
              DECLARE v DECIMAL(14,2);
              SELECT COALESCE(SUM(t.total_tagihan - COALESCE(d.dibayar, 0)), 0) INTO v
              FROM tagihan t
              JOIN rombel_siswa r ON r.id_rombel = t.id_rombel
              LEFT JOIN (SELECT a.id_tagihan, SUM(a.jumlah) AS dibayar
                         FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
                         WHERE p.status = 'Terverifikasi' GROUP BY a.id_tagihan) d ON d.id_tagihan = t.id_tagihan
              WHERE r.id_siswa = p_id_siswa AND t.jatuh_tempo < CURDATE() AND t.total_tagihan - COALESCE(d.dibayar, 0) > 0;
              RETURN v;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_batalkan_pembayaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_batalkan_pembayaran`(IN `p_id_pembayaran` INT, IN `p_id_user` INT, IN `p_alasan` VARCHAR(255))
            BEGIN
              DECLARE v_status VARCHAR(30);
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              IF p_alasan IS NULL OR p_alasan = '' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alasan pembatalan wajib diisi';
              END IF;
              START TRANSACTION;
                SELECT status INTO v_status FROM pembayaran WHERE id_pembayaran = p_id_pembayaran FOR UPDATE;
                IF v_status IS NULL OR v_status IN ('Ditolak','Dibatalkan') THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran tidak ditemukan atau sudah ditolak/dibatalkan';
                END IF;
                UPDATE pembayaran SET status = 'Dibatalkan', id_user_verifikator = p_id_user, catatan = p_alasan WHERE id_pembayaran = p_id_pembayaran;
              COMMIT;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_buat_tagihan_bulanan',
            'sql' => <<<'DB_TK_SQL'
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
                JOIN kelas k        ON k.id_kelas = r.id_kelas
                JOIN tarif tf       ON tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok
                JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
                WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
                  AND NOT EXISTS (SELECT 1 FROM tagihan x WHERE x.id_rombel = r.id_rombel AND x.id_jenis_biaya = tf.id_jenis_biaya AND x.periode = p_periode);
                SET v_dibuat = ROW_COUNT();
                SELECT COUNT(*) INTO v_tanpa_tarif
                FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
                WHERE k.id_tahun_ajaran = p_id_tahun_ajaran AND r.status = 'Aktif'
                  AND NOT EXISTS (SELECT 1 FROM tarif tf JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Bulanan'
                                   WHERE tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok);
              COMMIT;
              SELECT v_dibuat AS tagihan_dibuat, v_tanpa_tarif AS siswa_tanpa_tarif;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_buat_tagihan_kegiatan',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_buat_tagihan_kegiatan`(IN `p_id_kegiatan` INT)
            BEGIN
              DECLARE v_biaya DECIMAL(12,2);
              DECLARE v_tanggal DATE;
              DECLARE v_seluruh TINYINT;
              DECLARE v_jenis INT;
              DECLARE v_dibuat INT DEFAULT 0;
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              SELECT biaya, tanggal, seluruh_sekolah INTO v_biaya, v_tanggal, v_seluruh FROM kegiatan WHERE id_kegiatan = p_id_kegiatan;
              IF v_tanggal IS NULL THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kegiatan tidak ditemukan';
              END IF;
              IF v_biaya IS NULL OR v_biaya = 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kegiatan ini tidak berbiaya';
              END IF;
              SELECT id_jenis_biaya INTO v_jenis FROM jenis_biaya WHERE kategori = 'Kegiatan' ORDER BY id_jenis_biaya LIMIT 1;
              IF v_jenis IS NULL THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jenis biaya kategori Kegiatan belum ada';
              END IF;
              START TRANSACTION;
                INSERT INTO tagihan (id_rombel, id_jenis_biaya, id_kegiatan, nominal, diskon, jatuh_tempo)
                SELECT r.id_rombel, v_jenis, p_id_kegiatan, v_biaya, 0, v_tanggal
                FROM rombel_siswa r
                WHERE r.status = 'Aktif'
                  AND ((v_seluruh = 1 AND EXISTS (SELECT 1 FROM kelas k JOIN tahun_ajaran ta ON ta.id_tahun_ajaran = k.id_tahun_ajaran
                                                   WHERE k.id_kelas = r.id_kelas AND ta.is_aktif = 1))
                       OR EXISTS (SELECT 1 FROM kegiatan_kelas kk WHERE kk.id_kegiatan = p_id_kegiatan AND kk.id_kelas = r.id_kelas))
                  AND NOT EXISTS (SELECT 1 FROM tagihan x WHERE x.id_rombel = r.id_rombel AND x.id_jenis_biaya = v_jenis AND x.id_kegiatan = p_id_kegiatan);
                SET v_dibuat = ROW_COUNT();
              COMMIT;
              SELECT v_dibuat AS tagihan_dibuat;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_buat_tagihan_pendaftaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_buat_tagihan_pendaftaran`(IN `p_id_rombel` INT, IN `p_jatuh_tempo` DATE, OUT `p_dibuat` INT)
            BEGIN
              INSERT INTO tagihan (id_rombel, id_jenis_biaya, nominal, diskon, jatuh_tempo)
              SELECT r.id_rombel, tf.id_jenis_biaya, tf.nominal, 0, p_jatuh_tempo
              FROM rombel_siswa r
              JOIN kelas k        ON k.id_kelas = r.id_kelas
              JOIN tarif tf       ON tf.id_tahun_ajaran = k.id_tahun_ajaran AND tf.kelompok = k.kelompok
              JOIN jenis_biaya jb ON jb.id_jenis_biaya = tf.id_jenis_biaya AND jb.kategori = 'Pendaftaran'
              WHERE r.id_rombel = p_id_rombel
                AND NOT EXISTS (SELECT 1 FROM tagihan x WHERE x.id_rombel = r.id_rombel AND x.id_jenis_biaya = tf.id_jenis_biaya);
              SET p_dibuat = ROW_COUNT();
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_gabung_keluarga',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_gabung_keluarga`(IN `p_id_pertahankan` INT, IN `p_id_duplikat` INT)
            BEGIN
              DECLARE v_user INT;
              DECLARE v_alamat TEXT;
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              IF p_id_pertahankan = p_id_duplikat THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID yang dipertahankan dan duplikat tidak boleh sama';
              END IF;
              START TRANSACTION;
                SELECT id_user, alamat INTO v_user, v_alamat FROM keluarga WHERE id_keluarga = p_id_duplikat;
                UPDATE siswa SET id_keluarga = p_id_pertahankan WHERE id_keluarga = p_id_duplikat;
                UPDATE wali w LEFT JOIN wali x ON x.id_keluarga = p_id_pertahankan AND x.hubungan = w.hubungan
                   SET w.id_keluarga = p_id_pertahankan WHERE w.id_keluarga = p_id_duplikat AND x.id_wali IS NULL;
                INSERT INTO kontak_keluarga (id_keluarga, nomor, urutan)
                SELECT p_id_pertahankan, c.nomor,
                       COALESCE((SELECT MAX(m.urutan) FROM kontak_keluarga m WHERE m.id_keluarga = p_id_pertahankan), 0) + ROW_NUMBER() OVER (ORDER BY c.urutan)
                FROM kontak_keluarga c
                WHERE c.id_keluarga = p_id_duplikat
                  AND NOT EXISTS (SELECT 1 FROM kontak_keluarga m WHERE m.id_keluarga = p_id_pertahankan AND m.nomor = c.nomor);
                UPDATE keluarga SET id_user = NULL WHERE id_keluarga = p_id_duplikat;
                UPDATE keluarga SET id_user = COALESCE(id_user, v_user), alamat = COALESCE(alamat, v_alamat) WHERE id_keluarga = p_id_pertahankan;
                DELETE FROM keluarga WHERE id_keluarga = p_id_duplikat;
              COMMIT;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_gabung_siswa',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_gabung_siswa`(IN `p_id_pertahankan` INT, IN `p_id_duplikat` INT)
            BEGIN
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              IF p_id_pertahankan = p_id_duplikat THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID yang dipertahankan dan duplikat tidak boleh sama';
              END IF;
              IF NOT EXISTS (SELECT 1 FROM siswa WHERE id_siswa = p_id_pertahankan) OR NOT EXISTS (SELECT 1 FROM siswa WHERE id_siswa = p_id_duplikat) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Salah satu ID siswa tidak ditemukan';
              END IF;
              IF EXISTS (SELECT 1 FROM transaksi_tabungan WHERE id_siswa = p_id_duplikat)
                 OR EXISTS (SELECT 1 FROM rombel_siswa r WHERE r.id_siswa = p_id_duplikat AND
                            (EXISTS (SELECT 1 FROM absensi a WHERE a.id_rombel = r.id_rombel)
                          OR EXISTS (SELECT 1 FROM tagihan t WHERE t.id_rombel = r.id_rombel)
                          OR EXISTS (SELECT 1 FROM pengumpulan_tugas x WHERE x.id_rombel = r.id_rombel)
                          OR EXISTS (SELECT 1 FROM nilai_iqro n WHERE n.id_rombel = r.id_rombel)
                          OR EXISTS (SELECT 1 FROM rapor rp WHERE rp.id_rombel = r.id_rombel)
                          OR EXISTS (SELECT 1 FROM portofolio po WHERE po.id_rombel = r.id_rombel))) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Duplikat sudah punya data transaksi, gabungkan manual';
              END IF;
              START TRANSACTION;
                DELETE d FROM rombel_siswa d JOIN rombel_siswa k ON k.id_kelas = d.id_kelas AND k.id_siswa = p_id_pertahankan WHERE d.id_siswa = p_id_duplikat;
                UPDATE rombel_siswa SET id_siswa = p_id_pertahankan WHERE id_siswa = p_id_duplikat;
                DELETE d FROM pendaftaran d JOIN pendaftaran k ON k.id_tahun_ajaran = d.id_tahun_ajaran AND k.id_siswa = p_id_pertahankan WHERE d.id_siswa = p_id_duplikat;
                UPDATE pendaftaran SET id_siswa = p_id_pertahankan WHERE id_siswa = p_id_duplikat;
                UPDATE siswa k JOIN siswa d ON d.id_siswa = p_id_duplikat
                   SET k.tempat_lahir = COALESCE(k.tempat_lahir, d.tempat_lahir), k.tanggal_lahir = COALESCE(k.tanggal_lahir, d.tanggal_lahir),
                       k.anak_ke = COALESCE(k.anak_ke, d.anak_ke), k.alamat_domisili = COALESCE(k.alamat_domisili, d.alamat_domisili)
                 WHERE k.id_siswa = p_id_pertahankan;
                SET @g_induk = (SELECT no_induk FROM siswa WHERE id_siswa = p_id_duplikat);
                SET @g_nik   = (SELECT nik FROM siswa WHERE id_siswa = p_id_duplikat);
                SET @g_nisn  = (SELECT nisn FROM siswa WHERE id_siswa = p_id_duplikat);
                DELETE FROM siswa WHERE id_siswa = p_id_duplikat;
                UPDATE siswa SET no_induk = COALESCE(no_induk, @g_induk), nik = COALESCE(nik, @g_nik), nisn = COALESCE(nisn, @g_nisn)
                 WHERE id_siswa = p_id_pertahankan;
              COMMIT;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_inisialisasi_absensi',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_inisialisasi_absensi`(IN `p_id_kelas` INT, IN `p_tanggal` DATE, IN `p_id_user` INT)
            BEGIN
              DECLARE v_dibuat INT DEFAULT 0;
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
                INSERT INTO absensi (id_rombel, tanggal, status, id_user_pencatat)
                SELECT r.id_rombel, p_tanggal, 'Hadir', p_id_user FROM rombel_siswa r
                WHERE r.id_kelas = p_id_kelas AND r.status = 'Aktif'
                  AND NOT EXISTS (SELECT 1 FROM absensi a WHERE a.id_rombel = r.id_rombel AND a.tanggal = p_tanggal);
                SET v_dibuat = ROW_COUNT();
              COMMIT;
              SELECT v_dibuat AS baris_dibuat;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_laporan_keuangan',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_laporan_keuangan`(IN `p_dari` DATE, IN `p_sampai` DATE)
            BEGIN
              SELECT jb.nama AS jenis_biaya, p.metode, COUNT(DISTINCT p.id_pembayaran) AS jumlah_kuitansi, COALESCE(SUM(a.jumlah), 0) AS total
              FROM pembayaran p
              JOIN alokasi_pembayaran a ON a.id_pembayaran = p.id_pembayaran
              JOIN tagihan t            ON t.id_tagihan = a.id_tagihan
              JOIN jenis_biaya jb       ON jb.id_jenis_biaya = t.id_jenis_biaya
              WHERE p.status = 'Terverifikasi' AND DATE(p.tanggal_bayar) BETWEEN p_dari AND p_sampai
              GROUP BY jb.nama, p.metode;
              SELECT COUNT(*) AS tagihan_terbit, COALESCE(SUM(total_tagihan), 0) AS nilai_tagihan,
                     (SELECT COALESCE(SUM(sisa), 0) FROM v_tagihan_detail WHERE sisa > 0 AND jatuh_tempo < CURDATE()) AS total_tunggakan_saat_ini
              FROM tagihan WHERE jatuh_tempo BETWEEN p_dari AND p_sampai;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_rekap_kehadiran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_rekap_kehadiran`(IN `p_id_kelas` INT, IN `p_bulan` TINYINT, IN `p_tahun` SMALLINT)
            BEGIN
              SELECT * FROM v_rekap_kehadiran_bulanan WHERE id_kelas = p_id_kelas AND bulan = p_bulan AND tahun = p_tahun ORDER BY nama_lengkap;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_validasi_rombel',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_validasi_rombel`(IN `p_id_siswa` INT, IN `p_id_kelas` INT, IN `p_abaikan_rombel` INT)
            BEGIN
              DECLARE v_ta INT;
              SELECT id_tahun_ajaran INTO v_ta FROM kelas WHERE id_kelas = p_id_kelas;
              IF EXISTS (SELECT 1 FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
                         WHERE r.id_siswa = p_id_siswa AND k.id_tahun_ajaran = v_ta AND r.id_rombel <> COALESCE(p_abaikan_rombel, 0)) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa sudah punya kelas pada tahun ajaran tersebut';
              END IF;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_verifikasi_pembayaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_verifikasi_pembayaran`(IN `p_id_pembayaran` INT, IN `p_setuju` TINYINT, IN `p_id_user` INT, IN `p_catatan` VARCHAR(255))
            BEGIN
              DECLARE v_status VARCHAR(30);
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
                SELECT status INTO v_status FROM pembayaran WHERE id_pembayaran = p_id_pembayaran FOR UPDATE;
                IF v_status IS NULL THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran tidak ditemukan';
                END IF;
                IF v_status <> 'Menunggu Verifikasi' THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pembayaran berstatus Menunggu Verifikasi yang dapat diverifikasi';
                END IF;
                UPDATE pembayaran SET status = IF(p_setuju = 1, 'Terverifikasi', 'Ditolak'), id_user_verifikator = p_id_user, catatan = p_catatan
                 WHERE id_pembayaran = p_id_pembayaran;
              COMMIT;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_verifikasi_pendaftaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_verifikasi_pendaftaran`(IN `p_id_pendaftaran` INT, IN `p_setuju` TINYINT, IN `p_id_user` INT, IN `p_catatan` VARCHAR(255))
            BEGIN
              DECLARE v_status VARCHAR(20);
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
                SELECT status INTO v_status FROM pendaftaran WHERE id_pendaftaran = p_id_pendaftaran FOR UPDATE;
                IF v_status IS NULL THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran tidak ditemukan';
                END IF;
                IF v_status <> 'Diajukan' THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pendaftaran berstatus Diajukan yang dapat diverifikasi';
                END IF;
                IF p_setuju = 1 AND EXISTS (SELECT 1 FROM dokumen_pendaftaran WHERE id_pendaftaran = p_id_pendaftaran AND status_verifikasi <> 'Valid') THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada dokumen yang belum valid';
                END IF;
                UPDATE pendaftaran SET status = IF(p_setuju = 1, 'Diverifikasi', 'Ditolak'), catatan_verifikasi = p_catatan, id_user_verifikator = p_id_user
                 WHERE id_pendaftaran = p_id_pendaftaran;
              COMMIT;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_sisa_tagihan',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_sisa_tagihan`(`p_id_tagihan` INT) RETURNS decimal(12,2)
                READS SQL DATA
            BEGIN
              DECLARE v_total DECIMAL(12,2);
              SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
              IF v_total IS NULL THEN RETURN 0; END IF;
              RETURN v_total - fn_dibayar_tagihan(p_id_tagihan);
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'FUNCTION',
            'name' => 'fn_status_tagihan',
            'sql' => <<<'DB_TK_SQL'
            CREATE FUNCTION `fn_status_tagihan`(`p_id_tagihan` INT) RETURNS varchar(12) CHARSET utf8mb4
                READS SQL DATA
            BEGIN
              DECLARE v_total   DECIMAL(12,2);
              DECLARE v_dibayar DECIMAL(12,2);
              SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
              IF v_total IS NULL THEN RETURN NULL; END IF;
              SET v_dibayar = fn_dibayar_tagihan(p_id_tagihan);
              RETURN IF(v_dibayar >= v_total, 'Lunas', IF(v_dibayar > 0, 'Sebagian', 'Belum Bayar'));
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_catat_pembayaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_catat_pembayaran`(IN `p_metode` VARCHAR(10), IN `p_bukti` VARCHAR(255), IN `p_id_user` INT, IN `p_alokasi` JSON)
            BEGIN
              DECLARE v_id INT;
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              IF p_alokasi IS NULL OR JSON_LENGTH(p_alokasi) = 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Daftar tagihan yang dibayar tidak boleh kosong';
              END IF;
              START TRANSACTION;
                INSERT INTO pembayaran (metode, bukti_transfer, status, id_user_pencatat)
                VALUES (p_metode, p_bukti, IF(p_metode = 'Tunai', 'Terverifikasi', 'Menunggu Verifikasi'), p_id_user);
                SET v_id = LAST_INSERT_ID();
                INSERT INTO alokasi_pembayaran (id_pembayaran, id_tagihan, jumlah)
                SELECT v_id, j.id_tagihan, j.jumlah
                FROM JSON_TABLE(p_alokasi, '$[*]' COLUMNS (id_tagihan INT PATH '$.id_tagihan', jumlah DECIMAL(12,2) PATH '$.jumlah')) AS j;
              COMMIT;
              SELECT p.id_pembayaran, fn_no_kuitansi(p.id_pembayaran, p.tanggal_bayar) AS no_kuitansi, p.status,
                     (SELECT SUM(a.jumlah) FROM alokasi_pembayaran a WHERE a.id_pembayaran = p.id_pembayaran) AS total_bayar
              FROM pembayaran p WHERE p.id_pembayaran = v_id;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_portal_ringkasan_anak',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_portal_ringkasan_anak`(IN `p_id_user` INT)
            BEGIN
              SELECT s.id_siswa, s.nama_lengkap, kl.nama_kelas, kl.kelompok, ta.nama AS tahun_ajaran, fn_hitung_usia(s.tanggal_lahir) AS usia,
                     fn_persen_kehadiran(s.id_siswa, DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE()) AS persen_hadir_30_hari,
                     fn_total_tunggakan_siswa(s.id_siswa) AS tunggakan, fn_saldo_tabungan(s.id_siswa) AS saldo_tabungan
              FROM keluarga f
              JOIN siswa s         ON s.id_keluarga = f.id_keluarga
              JOIN rombel_siswa r  ON r.id_siswa = s.id_siswa AND r.status = 'Aktif'
              JOIN kelas kl        ON kl.id_kelas = r.id_kelas
              JOIN tahun_ajaran ta ON ta.id_tahun_ajaran = kl.id_tahun_ajaran
              WHERE f.id_user = p_id_user;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_terima_pendaftaran',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_terima_pendaftaran`(IN `p_id_pendaftaran` INT, IN `p_id_kelas` INT, IN `p_id_user` INT, IN `p_jatuh_tempo` DATE)
            BEGIN
              DECLARE v_status VARCHAR(20);
              DECLARE v_siswa INT;
              DECLARE v_ta INT;
              DECLARE v_kelompok VARCHAR(10);
              DECLARE v_ta_kelas INT;
              DECLARE v_kel_kelas VARCHAR(10);
              DECLARE v_rombel INT;
              DECLARE v_tagihan INT DEFAULT 0;
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
                SELECT status, id_siswa, id_tahun_ajaran, kelompok INTO v_status, v_siswa, v_ta, v_kelompok
                  FROM pendaftaran WHERE id_pendaftaran = p_id_pendaftaran FOR UPDATE;
                IF v_status IS NULL THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran tidak ditemukan';
                END IF;
                IF v_status <> 'Diverifikasi' THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran harus berstatus Diverifikasi sebelum diterima';
                END IF;
                SELECT id_tahun_ajaran, kelompok INTO v_ta_kelas, v_kel_kelas FROM kelas WHERE id_kelas = p_id_kelas;
                IF v_ta_kelas IS NULL OR v_ta_kelas <> v_ta OR v_kel_kelas <> v_kelompok THEN
                  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kelas tidak sesuai tahun ajaran dan kelompok tujuan pendaftaran';
                END IF;
                INSERT INTO rombel_siswa (id_siswa, id_kelas, status, tanggal_masuk) VALUES (v_siswa, p_id_kelas, 'Aktif', CURDATE());
                SET v_rombel = LAST_INSERT_ID();
                IF p_jatuh_tempo IS NOT NULL THEN
                  CALL sp_buat_tagihan_pendaftaran(v_rombel, p_jatuh_tempo, v_tagihan);
                END IF;
                UPDATE pendaftaran SET status = 'Diterima', id_user_verifikator = COALESCE(p_id_user, id_user_verifikator) WHERE id_pendaftaran = p_id_pendaftaran;
              COMMIT;
              SELECT v_siswa AS id_siswa, v_rombel AS id_rombel, v_tagihan AS tagihan_pendaftaran_dibuat;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_transaksi_tabungan',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_transaksi_tabungan`(IN `p_id_siswa` INT, IN `p_jenis` VARCHAR(10), IN `p_jumlah` DECIMAL(14,2), IN `p_metode` VARCHAR(10), IN `p_keterangan` VARCHAR(255), IN `p_id_user` INT)
            BEGIN
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
                INSERT INTO transaksi_tabungan (id_siswa, jenis, jumlah, metode, keterangan, id_user_pencatat)
                VALUES (p_id_siswa, p_jenis, p_jumlah, p_metode, p_keterangan, p_id_user);
              COMMIT;
              SELECT fn_saldo_tabungan(p_id_siswa) AS saldo_terbaru;
            END
            DB_TK_SQL,
        ],
        [
            'type' => 'PROCEDURE',
            'name' => 'sp_bayar_tagihan',
            'sql' => <<<'DB_TK_SQL'
            CREATE PROCEDURE `sp_bayar_tagihan`(IN `p_id_tagihan` INT, IN `p_jumlah` DECIMAL(12,2), IN `p_metode` VARCHAR(10), IN `p_bukti` VARCHAR(255), IN `p_id_user` INT)
            BEGIN
              CALL sp_catat_pembayaran(p_metode, p_bukti, p_id_user, JSON_ARRAY(JSON_OBJECT('id_tagihan', p_id_tagihan, 'jumlah', p_jumlah)));
            END
            DB_TK_SQL,
        ],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::OBJECTS as $object) {
            if ($this->exists($object)) {
                throw new RuntimeException('The db_tk_final SQL object migrations require an empty MySQL database.');
            }

            DB::unprepared($object['sql']);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (array_reverse(self::OBJECTS) as $object) {
            DB::unprepared('DROP '.$object['type'].' IF EXISTS `'.$object['name'].'`');
        }
    }

    private function exists(array $object): bool
    {
        $schema = DB::connection()->getDatabaseName();

        [$table, $nameColumn, $typeClause, $bindings] = match ($object['type']) {
            'FUNCTION', 'PROCEDURE' => ['ROUTINES', 'ROUTINE_NAME', ' AND ROUTINE_TYPE = ?', [$object['type']]],
            'TRIGGER' => ['TRIGGERS', 'TRIGGER_NAME', '', []],
            'VIEW' => ['VIEWS', 'TABLE_NAME', '', []],
            default => throw new RuntimeException('Unsupported SQL object type.'),
        };

        return DB::selectOne(
            "SELECT 1 FROM information_schema.{$table} WHERE ".($object['type'] === 'VIEW' ? 'TABLE_SCHEMA' : ($object['type'] === 'TRIGGER' ? 'TRIGGER_SCHEMA' : 'ROUTINE_SCHEMA'))." = ? AND {$nameColumn} = ?{$typeClause} LIMIT 1",
            array_merge([$schema, $object['name']], $bindings),
        ) !== null;
    }
};
