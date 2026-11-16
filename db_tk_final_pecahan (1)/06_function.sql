-- Function (14)
-- Impor ke database db_tk_final (buat dulu databasenya, utf8mb4_0900_ai_ci). Urutan: lihat BACA_DULU.txt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

DELIMITER $$
CREATE FUNCTION `fn_akhir_semester` (`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS DATE DETERMINISTIC NO SQL BEGIN
  RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-12-31') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-06-30') AS DATE));
END$$
CREATE FUNCTION `fn_awal_semester` (`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS DATE DETERMINISTIC NO SQL BEGIN
  RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-07-01') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-01-01') AS DATE));
END$$
CREATE FUNCTION `fn_dibayar_tagihan` (`p_id_tagihan` INT) RETURNS DECIMAL(12,2) READS SQL DATA BEGIN
  DECLARE v DECIMAL(12,2);
  SELECT COALESCE(SUM(a.jumlah), 0) INTO v
    FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
   WHERE a.id_tagihan = p_id_tagihan AND p.status = 'Terverifikasi';
  RETURN v;
END$$
CREATE FUNCTION `fn_hitung_usia` (`p_tanggal_lahir` DATE) RETURNS VARCHAR(30) CHARSET utf8mb4 NO SQL BEGIN
  DECLARE v_bulan INT;
  IF p_tanggal_lahir IS NULL THEN RETURN NULL; END IF;
  SET v_bulan = TIMESTAMPDIFF(MONTH, p_tanggal_lahir, CURDATE());
  RETURN CONCAT(FLOOR(v_bulan / 12), ' tahun ', MOD(v_bulan, 12), ' bulan');
END$$
CREATE FUNCTION `fn_no_kuitansi` (`p_id` INT, `p_tanggal` DATETIME) RETURNS VARCHAR(20) CHARSET utf8mb4 DETERMINISTIC NO SQL BEGIN
  RETURN CONCAT('KWT-', YEAR(p_tanggal), '-', LPAD(p_id, 5, '0'));
END$$
CREATE FUNCTION `fn_no_pendaftaran` (`p_id` INT, `p_tahun_mulai` SMALLINT) RETURNS VARCHAR(20) CHARSET utf8mb4 DETERMINISTIC NO SQL BEGIN
  RETURN CONCAT('PPDB-', p_tahun_mulai, '-', LPAD(p_id, 4, '0'));
END$$
CREATE FUNCTION `fn_persen_kehadiran` (`p_id_siswa` INT, `p_dari` DATE, `p_sampai` DATE) RETURNS DECIMAL(5,2) READS SQL DATA BEGIN
  DECLARE v DECIMAL(5,2);
  SELECT ROUND(100 * SUM(a.status = 'Hadir') / NULLIF(COUNT(*), 0), 2) INTO v
    FROM absensi a JOIN rombel_siswa r ON r.id_rombel = a.id_rombel
   WHERE r.id_siswa = p_id_siswa AND a.tanggal BETWEEN p_dari AND p_sampai;
  RETURN v;
END$$
CREATE FUNCTION `fn_saldo_tabungan` (`p_id_siswa` INT) RETURNS DECIMAL(14,2) READS SQL DATA BEGIN
  DECLARE v DECIMAL(14,2);
  SELECT COALESCE(SUM(IF(jenis = 'Setor', jumlah, -jumlah)), 0) INTO v FROM transaksi_tabungan WHERE id_siswa = p_id_siswa;
  RETURN v;
END$$
CREATE FUNCTION `fn_semester` (`p_tanggal` DATE) RETURNS VARCHAR(6) CHARSET utf8mb4 DETERMINISTIC NO SQL BEGIN
  RETURN IF(MONTH(p_tanggal) >= 7, 'Ganjil', 'Genap');
END$$
CREATE FUNCTION `fn_sisa_tagihan` (`p_id_tagihan` INT) RETURNS DECIMAL(12,2) READS SQL DATA BEGIN
  DECLARE v_total DECIMAL(12,2);
  SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
  IF v_total IS NULL THEN RETURN 0; END IF;
  RETURN v_total - fn_dibayar_tagihan(p_id_tagihan);
END$$
CREATE FUNCTION `fn_status_tagihan` (`p_id_tagihan` INT) RETURNS VARCHAR(12) CHARSET utf8mb4 READS SQL DATA BEGIN
  DECLARE v_total   DECIMAL(12,2);
  DECLARE v_dibayar DECIMAL(12,2);
  SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
  IF v_total IS NULL THEN RETURN NULL; END IF;
  SET v_dibayar = fn_dibayar_tagihan(p_id_tagihan);
  RETURN IF(v_dibayar >= v_total, 'Lunas', IF(v_dibayar > 0, 'Sebagian', 'Belum Bayar'));
END$$
CREATE FUNCTION `fn_title_case` (`p_teks` VARCHAR(255)) RETURNS VARCHAR(255) CHARSET utf8mb4 DETERMINISTIC NO SQL BEGIN
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
END$$
CREATE FUNCTION `fn_total_tunggakan_keluarga` (`p_id_keluarga` INT) RETURNS DECIMAL(14,2) READS SQL DATA BEGIN
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
END$$
CREATE FUNCTION `fn_total_tunggakan_siswa` (`p_id_siswa` INT) RETURNS DECIMAL(14,2) READS SQL DATA BEGIN
  DECLARE v DECIMAL(14,2);
  SELECT COALESCE(SUM(t.total_tagihan - COALESCE(d.dibayar, 0)), 0) INTO v
  FROM tagihan t
  JOIN rombel_siswa r ON r.id_rombel = t.id_rombel
  LEFT JOIN (SELECT a.id_tagihan, SUM(a.jumlah) AS dibayar
             FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
             WHERE p.status = 'Terverifikasi' GROUP BY a.id_tagihan) d ON d.id_tagihan = t.id_tagihan
  WHERE r.id_siswa = p_id_siswa AND t.jatuh_tempo < CURDATE() AND t.total_tagihan - COALESCE(d.dibayar, 0) > 0;
  RETURN v;
END$$
DELIMITER ;

