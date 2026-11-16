-- Trigger (34)
-- Impor ke database db_tk_final (buat dulu databasenya, utf8mb4_0900_ai_ci). Urutan: lihat BACA_DULU.txt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

DELIMITER $$
CREATE TRIGGER `trg_absensi_bi` BEFORE INSERT ON `absensi` FOR EACH ROW BEGIN
  IF NEW.tanggal > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Absensi tidak boleh untuk tanggal yang belum terjadi';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_absensi_bu` BEFORE UPDATE ON `absensi` FOR EACH ROW BEGIN
  IF NEW.tanggal > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Absensi tidak boleh untuk tanggal yang belum terjadi';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_alokasi_bd` BEFORE DELETE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran tidak boleh dihapus';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_alokasi_bi` BEFORE INSERT ON `alokasi_pembayaran` FOR EACH ROW BEGIN
  DECLARE v_status VARCHAR(30);
  DECLARE v_total  DECIMAL(12,2);
  DECLARE v_masuk  DECIMAL(12,2);
  SELECT status INTO v_status FROM pembayaran WHERE id_pembayaran = NEW.id_pembayaran;
  IF v_status IS NULL OR v_status IN ('Ditolak','Dibatalkan') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kuitansi tidak valid untuk menerima alokasi';
  END IF;
  SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = NEW.id_tagihan FOR UPDATE;
  IF v_total IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan tidak ditemukan';
  END IF;
  SELECT COALESCE(SUM(a.jumlah), 0) INTO v_masuk
    FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
   WHERE a.id_tagihan = NEW.id_tagihan AND p.status IN ('Terverifikasi','Menunggu Verifikasi');
  IF v_masuk + NEW.jumlah > v_total THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran melebihi sisa tagihan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_alokasi_bu` BEFORE UPDATE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran tidak boleh diubah, batalkan kuitansi lalu catat ulang';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_guru_bi` BEFORE INSERT ON `guru` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_guru_bu` BEFORE UPDATE ON `guru` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_kegiatan_kelas_bi` BEFORE INSERT ON `kegiatan_kelas` FOR EACH ROW BEGIN
  IF (SELECT seluruh_sekolah FROM kegiatan WHERE id_kegiatan = NEW.id_kegiatan) = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kegiatan seluruh sekolah tidak perlu dikaitkan ke kelas tertentu';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_nilai_iqro_bi` BEFORE INSERT ON `nilai_iqro` FOR EACH ROW BEGIN
  DECLARE v_ind VARCHAR(10);
  DECLARE v_kelas VARCHAR(10);
  SELECT kelompok INTO v_ind FROM indikator_iqro WHERE id_indikator = NEW.id_indikator;
  SELECT kl.kelompok INTO v_kelas FROM rombel_siswa r JOIN kelas kl ON kl.id_kelas = r.id_kelas WHERE r.id_rombel = NEW.id_rombel;
  IF v_ind IS NULL OR v_kelas IS NULL OR v_ind <> v_kelas THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Indikator tidak sesuai dengan kelompok siswa';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_nilai_iqro_bu` BEFORE UPDATE ON `nilai_iqro` FOR EACH ROW BEGIN
  DECLARE v_ind VARCHAR(10);
  DECLARE v_kelas VARCHAR(10);
  IF NEW.id_indikator <> OLD.id_indikator OR NEW.id_rombel <> OLD.id_rombel THEN
    SELECT kelompok INTO v_ind FROM indikator_iqro WHERE id_indikator = NEW.id_indikator;
    SELECT kl.kelompok INTO v_kelas FROM rombel_siswa r JOIN kelas kl ON kl.id_kelas = r.id_kelas WHERE r.id_rombel = NEW.id_rombel;
    IF v_ind IS NULL OR v_kelas IS NULL OR v_ind <> v_kelas THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Indikator tidak sesuai dengan kelompok siswa';
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_au` AFTER UPDATE ON `pembayaran` FOR EACH ROW BEGIN
  IF OLD.status <> NEW.status THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('pembayaran', OLD.id_pembayaran, 'UPDATE', JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status, 'verifikator', NEW.id_user_verifikator), CURRENT_USER(), @app_user_id);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_bd` BEFORE DELETE ON `pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran tidak boleh dihapus, ubah status menjadi Dibatalkan';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_bi` BEFORE INSERT ON `pembayaran` FOR EACH ROW BEGIN
  IF NEW.metode = 'Transfer' AND (NEW.bukti_transfer IS NULL OR NEW.bukti_transfer = '') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran transfer wajib melampirkan bukti transfer';
  END IF;
  IF NEW.status = 'Terverifikasi' AND NEW.tanggal_verifikasi IS NULL THEN
    SET NEW.tanggal_verifikasi = NOW();
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pembayaran_bu` BEFORE UPDATE ON `pembayaran` FOR EACH ROW BEGIN
  IF OLD.status <> NEW.status THEN
    IF OLD.status IN ('Ditolak','Dibatalkan') OR (OLD.status = 'Terverifikasi' AND NEW.status <> 'Dibatalkan') THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Perubahan status pembayaran tidak diizinkan';
    END IF;
    IF NEW.tanggal_verifikasi IS NULL AND NEW.status IN ('Terverifikasi','Ditolak') THEN
      SET NEW.tanggal_verifikasi = NOW();
    END IF;
  END IF;
  IF OLD.status = 'Terverifikasi' AND (NEW.metode <> OLD.metode OR NEW.tanggal_bayar <> OLD.tanggal_bayar) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran terverifikasi tidak boleh diubah, batalkan lalu catat ulang';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pendaftaran_bu` BEFORE UPDATE ON `pendaftaran` FOR EACH ROW BEGIN
  IF OLD.status <> NEW.status THEN
    IF OLD.status = 'Diterima' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran yang sudah diterima tidak dapat diubah statusnya';
    END IF;
    IF NEW.status = 'Diterima' AND OLD.status <> 'Diverifikasi' THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pendaftaran harus diverifikasi sebelum diterima';
    END IF;
    IF NEW.status IN ('Diverifikasi','Ditolak') AND NEW.tanggal_verifikasi IS NULL THEN
      SET NEW.tanggal_verifikasi = NOW();
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pengumpulan_bi` BEFORE INSERT ON `pengumpulan_tugas` FOR EACH ROW BEGIN
  DECLARE v_kelas_tugas INT;
  DECLARE v_kelas_rombel INT;
  SELECT id_kelas INTO v_kelas_tugas FROM tugas WHERE id_tugas = NEW.id_tugas;
  SELECT id_kelas INTO v_kelas_rombel FROM rombel_siswa WHERE id_rombel = NEW.id_rombel;
  IF v_kelas_tugas IS NULL OR v_kelas_rombel IS NULL OR v_kelas_tugas <> v_kelas_rombel THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa bukan anggota kelas pemilik tugas ini';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pengumpulan_bu` BEFORE UPDATE ON `pengumpulan_tugas` FOR EACH ROW BEGIN
  IF NEW.nilai IS NOT NULL AND (OLD.nilai IS NULL OR NEW.nilai <> OLD.nilai) THEN
    IF NEW.id_guru_penilai IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Guru penilai wajib diisi saat memberi nilai';
    END IF;
    SET NEW.dinilai_pada = NOW();
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rapor_bu` BEFORE UPDATE ON `rapor` FOR EACH ROW BEGIN
  -- rapor Final terkunci; hanya refleksi orang tua yang boleh diisi. Ubah status ke Draft untuk mengedit.
  IF OLD.status = 'Final' AND NEW.status = 'Final' AND NOT (
       NEW.tanggal_rapor <=> OLD.tanggal_rapor AND NEW.tinggi_badan <=> OLD.tinggi_badan AND NEW.berat_badan <=> OLD.berat_badan
       AND NEW.narasi_agama_budi_pekerti <=> OLD.narasi_agama_budi_pekerti AND NEW.narasi_jati_diri <=> OLD.narasi_jati_diri
       AND NEW.narasi_literasi_steam <=> OLD.narasi_literasi_steam AND NEW.narasi_p5 <=> OLD.narasi_p5
       AND NEW.id_guru_kelas <=> OLD.id_guru_kelas AND NEW.id_guru_iqro <=> OLD.id_guru_iqro) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rapor Final terkunci; ubah status ke Draft untuk mengedit';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rombel_bi` BEFORE INSERT ON `rombel_siswa` FOR EACH ROW BEGIN
  CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rombel_bu` BEFORE UPDATE ON `rombel_siswa` FOR EACH ROW BEGIN
  IF NEW.id_siswa <> OLD.id_siswa OR NEW.id_kelas <> OLD.id_kelas THEN
    CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, OLD.id_rombel);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_siswa_ad` AFTER DELETE ON `siswa` FOR EACH ROW BEGIN
  INSERT INTO audit_log (tabel, id_record, aksi, data_lama, db_user, app_user_id)
  VALUES ('siswa', OLD.id_siswa, 'DELETE',
    JSON_OBJECT('nama_lengkap', OLD.nama_lengkap, 'nik', OLD.nik, 'nisn', OLD.nisn, 'tanggal_lahir', OLD.tanggal_lahir, 'id_keluarga', OLD.id_keluarga),
    CURRENT_USER(), @app_user_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_siswa_au` AFTER UPDATE ON `siswa` FOR EACH ROW BEGIN
  INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
  VALUES ('siswa', OLD.id_siswa, 'UPDATE',
    JSON_OBJECT('nama_lengkap', OLD.nama_lengkap, 'nik', OLD.nik, 'nisn', OLD.nisn, 'tanggal_lahir', OLD.tanggal_lahir, 'id_keluarga', OLD.id_keluarga),
    JSON_OBJECT('nama_lengkap', NEW.nama_lengkap, 'nik', NEW.nik, 'nisn', NEW.nisn, 'tanggal_lahir', NEW.tanggal_lahir, 'id_keluarga', NEW.id_keluarga),
    CURRENT_USER(), @app_user_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_siswa_bi` BEFORE INSERT ON `siswa` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.nama_panggilan IS NOT NULL THEN SET NEW.nama_panggilan = fn_title_case(NEW.nama_panggilan); END IF;
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_siswa_bu` BEFORE UPDATE ON `siswa` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.nama_panggilan IS NOT NULL THEN SET NEW.nama_panggilan = fn_title_case(NEW.nama_panggilan); END IF;
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tagihan_au` AFTER UPDATE ON `tagihan` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal OR NEW.diskon <> OLD.diskon OR NEW.jatuh_tempo <> OLD.jatuh_tempo THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('tagihan', OLD.id_tagihan, 'UPDATE',
      JSON_OBJECT('nominal', OLD.nominal, 'diskon', OLD.diskon, 'jatuh_tempo', OLD.jatuh_tempo),
      JSON_OBJECT('nominal', NEW.nominal, 'diskon', NEW.diskon, 'jatuh_tempo', NEW.jatuh_tempo), CURRENT_USER(), @app_user_id);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tagihan_bd` BEFORE DELETE ON `tagihan` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM alokasi_pembayaran WHERE id_tagihan = OLD.id_tagihan) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan yang sudah memiliki pembayaran tidak boleh dihapus';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tagihan_bi` BEFORE INSERT ON `tagihan` FOR EACH ROW BEGIN
  DECLARE v_kategori VARCHAR(20);
  SELECT kategori INTO v_kategori FROM jenis_biaya WHERE id_jenis_biaya = NEW.id_jenis_biaya;
  IF v_kategori = 'Bulanan' AND (NEW.periode IS NULL OR NEW.id_kegiatan IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan bulanan wajib punya periode dan tidak terkait kegiatan';
  END IF;
  IF v_kategori = 'Kegiatan' AND (NEW.id_kegiatan IS NULL OR NEW.periode IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan kegiatan wajib terkait kegiatan dan tanpa periode';
  END IF;
  IF v_kategori = 'Pendaftaran' AND (NEW.periode IS NOT NULL OR NEW.id_kegiatan IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan pendaftaran tidak memakai periode atau kegiatan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tagihan_bu` BEFORE UPDATE ON `tagihan` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal OR NEW.diskon <> OLD.diskon THEN
    IF fn_dibayar_tagihan(OLD.id_tagihan) > (NEW.nominal - NEW.diskon) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Total tagihan baru lebih kecil dari pembayaran yang sudah masuk';
    END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tarif_au` AFTER UPDATE ON `tarif` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('tarif', OLD.id_tarif, 'UPDATE', JSON_OBJECT('nominal', OLD.nominal), JSON_OBJECT('nominal', NEW.nominal), CURRENT_USER(), @app_user_id);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_ttab_bd` BEFORE DELETE ON `transaksi_tabungan` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh dihapus, buat transaksi koreksi';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_ttab_bi` BEFORE INSERT ON `transaksi_tabungan` FOR EACH ROW BEGIN
  DECLARE v_siswa INT;
  SELECT id_siswa INTO v_siswa FROM siswa WHERE id_siswa = NEW.id_siswa FOR UPDATE;
  IF v_siswa IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa tidak ditemukan';
  END IF;
  IF NEW.jenis = 'Tarik' AND fn_saldo_tabungan(NEW.id_siswa) < NEW.jumlah THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo tabungan tidak mencukupi';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_ttab_bu` BEFORE UPDATE ON `transaksi_tabungan` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh diubah, buat transaksi koreksi';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_wali_bi` BEFORE INSERT ON `wali` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_wali_bu` BEFORE UPDATE ON `wali` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END
$$
DELIMITER ;

