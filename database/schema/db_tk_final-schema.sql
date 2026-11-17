/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `absensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `absensi` (
  `id_absensi` int NOT NULL AUTO_INCREMENT,
  `id_rombel` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Izin','Sakit','Alfa') NOT NULL DEFAULT 'Hadir',
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_absensi`),
  UNIQUE KEY `uq_absensi` (`id_rombel`,`tanggal`),
  KEY `idx_absensi_tanggal` (`tanggal`),
  KEY `fk_absensi_user` (`id_user_pencatat`),
  CONSTRAINT `fk_absensi_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_absensi_user` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_absensi_bi` BEFORE INSERT ON `absensi` FOR EACH ROW BEGIN
  IF NEW.tanggal > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Absensi tidak boleh untuk tanggal yang belum terjadi';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_absensi_bu` BEFORE UPDATE ON `absensi` FOR EACH ROW BEGIN
  IF NEW.tanggal > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Absensi tidak boleh untuk tanggal yang belum terjadi';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `alokasi_pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alokasi_pembayaran` (
  `id_alokasi` int NOT NULL AUTO_INCREMENT,
  `id_pembayaran` int NOT NULL,
  `id_tagihan` int NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_alokasi`),
  UNIQUE KEY `uq_alokasi` (`id_pembayaran`,`id_tagihan`),
  KEY `idx_alokasi_tagihan` (`id_tagihan`),
  CONSTRAINT `fk_alokasi_bayar` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_alokasi_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_alokasi_bi` BEFORE INSERT ON `alokasi_pembayaran` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_alokasi_bu` BEFORE UPDATE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran tidak boleh diubah, batalkan kuitansi lalu catat ulang';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_alokasi_bd` BEFORE DELETE ON `alokasi_pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alokasi pembayaran tidak boleh dihapus';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `diskon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `diskon` (
  `id_diskon` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(60) NOT NULL,
  `tipe` enum('Persen','Nominal') NOT NULL,
  `nilai` decimal(12,2) NOT NULL,
  `kategori_biaya` enum('Bulanan','Pendaftaran','Kegiatan') NOT NULL DEFAULT 'Bulanan',
  PRIMARY KEY (`id_diskon`),
  UNIQUE KEY `uq_diskon_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dokumen_pendaftaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumen_pendaftaran` (
  `id_dokumen` int NOT NULL AUTO_INCREMENT,
  `id_pendaftaran` int NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status_verifikasi` enum('Menunggu','Valid','Tidak Valid') NOT NULL DEFAULT 'Menunggu',
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumen`),
  UNIQUE KEY `uq_dokumen` (`id_pendaftaran`,`jenis`),
  CONSTRAINT `fk_dokumen_pendaftaran` FOREIGN KEY (`id_pendaftaran`) REFERENCES `pendaftaran` (`id_pendaftaran`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dokumentasi_kegiatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumentasi_kegiatan` (
  `id_dokumentasi` int NOT NULL AUTO_INCREMENT,
  `id_kegiatan` int NOT NULL,
  `tahap` enum('Sebelum','Selama','Sesudah') NOT NULL DEFAULT 'Selama',
  `tipe` enum('Foto','Video','Dokumen') NOT NULL DEFAULT 'Foto',
  `file_path` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_upload` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumentasi`),
  KEY `idx_dok_kegiatan` (`id_kegiatan`,`tahap`),
  KEY `fk_dok_user` (`id_user_upload`),
  CONSTRAINT `fk_dok_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dok_user` FOREIGN KEY (`id_user_upload`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru` (
  `id_guru` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `nuptk` varchar(16) DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `gelar_depan` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `gelar_belakang` varchar(50) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') DEFAULT NULL,
  `no_hp` varchar(16) DEFAULT NULL,
  `alamat` text,
  `pendidikan` varchar(30) DEFAULT NULL,
  `jabatan` varchar(30) NOT NULL,
  `status_kepegawaian` varchar(20) NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `kepsek_unik` tinyint GENERATED ALWAYS AS (if(((`jabatan` = _utf8mb4'Kepala Sekolah') and (`status` = _utf8mb4'Aktif')),1,NULL)) VIRTUAL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_guru`),
  UNIQUE KEY `uq_guru_user` (`id_user`),
  UNIQUE KEY `uq_guru_nuptk` (`nuptk`),
  UNIQUE KEY `uq_guru_nik` (`nik`),
  UNIQUE KEY `uq_guru_kepsek` (`kepsek_unik`),
  CONSTRAINT `fk_guru_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_guru_bi` BEFORE INSERT ON `guru` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_guru_bu` BEFORE UPDATE ON `guru` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `guru_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_kelas` (
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `peran` enum('Wali Kelas','Pendamping') NOT NULL DEFAULT 'Wali Kelas',
  PRIMARY KEY (`id_kelas`,`id_guru`),
  KEY `fk_gk_guru` (`id_guru`),
  CONSTRAINT `fk_gk_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_gk_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indikator_iqro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indikator_iqro` (
  `id_indikator` int NOT NULL AUTO_INCREMENT,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `kategori` varchar(60) DEFAULT NULL,
  `nama` varchar(120) NOT NULL,
  `urutan` smallint NOT NULL,
  PRIMARY KEY (`id_indikator`),
  UNIQUE KEY `uq_indikator_urutan` (`kelompok`,`urutan`),
  UNIQUE KEY `uq_indikator_nama` (`kelompok`,`kategori`,`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jenis_biaya`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jenis_biaya` (
  `id_jenis_biaya` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(60) NOT NULL,
  `kategori` enum('Bulanan','Pendaftaran','Kegiatan') NOT NULL,
  PRIMARY KEY (`id_jenis_biaya`),
  UNIQUE KEY `uq_jenis_biaya` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kegiatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kegiatan` (
  `id_kegiatan` int NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(150) NOT NULL,
  `jenis` enum('Dalam Kelas','Luar Kelas','Field Trip','Acara','Evaluasi','Libur') NOT NULL,
  `tanggal` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `deskripsi` text,
  `biaya` decimal(12,2) DEFAULT NULL,
  `seluruh_sekolah` tinyint(1) NOT NULL DEFAULT '0',
  `id_user_pembuat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kegiatan`),
  KEY `idx_kegiatan_tanggal` (`tanggal`),
  KEY `fk_kegiatan_user` (`id_user_pembuat`),
  CONSTRAINT `fk_kegiatan_user` FOREIGN KEY (`id_user_pembuat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kegiatan_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kegiatan_kelas` (
  `id_kegiatan` int NOT NULL,
  `id_kelas` int NOT NULL,
  PRIMARY KEY (`id_kegiatan`,`id_kelas`),
  KEY `fk_kk_kelas` (`id_kelas`),
  CONSTRAINT `fk_kk_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kk_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_kegiatan_kelas_bi` BEFORE INSERT ON `kegiatan_kelas` FOR EACH ROW BEGIN
  IF (SELECT seluruh_sekolah FROM kegiatan WHERE id_kegiatan = NEW.id_kegiatan) = 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kegiatan seluruh sekolah tidak perlu dikaitkan ke kelas tertentu';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `kapasitas` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kelas`),
  UNIQUE KEY `uq_kelas` (`id_tahun_ajaran`,`kelompok`,`nama_kelas`),
  CONSTRAINT `fk_kelas_ta` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `keluarga`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `keluarga` (
  `id_keluarga` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `no_kk` varchar(16) DEFAULT NULL,
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_keluarga`),
  UNIQUE KEY `uq_keluarga_user` (`id_user`),
  UNIQUE KEY `uq_keluarga_kk` (`no_kk`),
  CONSTRAINT `fk_keluarga_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kontak_keluarga`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kontak_keluarga` (
  `id_kontak` int NOT NULL AUTO_INCREMENT,
  `id_keluarga` int NOT NULL,
  `nomor` varchar(16) NOT NULL,
  `urutan` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_kontak`),
  UNIQUE KEY `uq_kontak_nomor` (`id_keluarga`,`nomor`),
  UNIQUE KEY `uq_kontak_urutan` (`id_keluarga`,`urutan`),
  CONSTRAINT `fk_kontak_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nilai_iqro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nilai_iqro` (
  `id_nilai` int NOT NULL AUTO_INCREMENT,
  `id_rombel` int NOT NULL,
  `id_indikator` int NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `skala` enum('BB','MB','BSH','BSB') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nilai`),
  UNIQUE KEY `uq_nilai_iqro` (`id_rombel`,`id_indikator`,`semester`),
  KEY `fk_niqro_indikator` (`id_indikator`),
  CONSTRAINT `fk_niqro_indikator` FOREIGN KEY (`id_indikator`) REFERENCES `indikator_iqro` (`id_indikator`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_niqro_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_nilai_iqro_bi` BEFORE INSERT ON `nilai_iqro` FOR EACH ROW BEGIN
  DECLARE v_ind VARCHAR(10);
  DECLARE v_kelas VARCHAR(10);
  SELECT kelompok INTO v_ind FROM indikator_iqro WHERE id_indikator = NEW.id_indikator;
  SELECT kl.kelompok INTO v_kelas FROM rombel_siswa r JOIN kelas kl ON kl.id_kelas = r.id_kelas WHERE r.id_rombel = NEW.id_rombel;
  IF v_ind IS NULL OR v_kelas IS NULL OR v_ind <> v_kelas THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Indikator tidak sesuai dengan kelompok siswa';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_nilai_iqro_bu` BEFORE UPDATE ON `nilai_iqro` FOR EACH ROW BEGIN
  DECLARE v_ind VARCHAR(10);
  DECLARE v_kelas VARCHAR(10);
  IF NEW.id_indikator <> OLD.id_indikator OR NEW.id_rombel <> OLD.id_rombel THEN
    SELECT kelompok INTO v_ind FROM indikator_iqro WHERE id_indikator = NEW.id_indikator;
    SELECT kl.kelompok INTO v_kelas FROM rombel_siswa r JOIN kelas kl ON kl.id_kelas = r.id_kelas WHERE r.id_rombel = NEW.id_rombel;
    IF v_ind IS NULL OR v_kelas IS NULL OR v_ind <> v_kelas THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Indikator tidak sesuai dengan kelompok siswa';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metode` enum('Tunai','Transfer') NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu Verifikasi','Terverifikasi','Ditolak','Dibatalkan') NOT NULL DEFAULT 'Menunggu Verifikasi',
  `catatan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `id_user_verifikator` int DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pembayaran`),
  KEY `idx_bayar_tanggal` (`tanggal_bayar`),
  KEY `fk_bayar_pencatat` (`id_user_pencatat`),
  KEY `fk_bayar_verifikator` (`id_user_verifikator`),
  CONSTRAINT `fk_bayar_pencatat` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_bayar_verifikator` FOREIGN KEY (`id_user_verifikator`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pembayaran_bi` BEFORE INSERT ON `pembayaran` FOR EACH ROW BEGIN
  IF NEW.metode = 'Transfer' AND (NEW.bukti_transfer IS NULL OR NEW.bukti_transfer = '') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran transfer wajib melampirkan bukti transfer';
  END IF;
  IF NEW.status = 'Terverifikasi' AND NEW.tanggal_verifikasi IS NULL THEN
    SET NEW.tanggal_verifikasi = NOW();
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pembayaran_bu` BEFORE UPDATE ON `pembayaran` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pembayaran_au` AFTER UPDATE ON `pembayaran` FOR EACH ROW BEGIN
  IF OLD.status <> NEW.status THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('pembayaran', OLD.id_pembayaran, 'UPDATE', JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status, 'verifikator', NEW.id_user_verifikator), CURRENT_USER(), @app_user_id);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pembayaran_bd` BEFORE DELETE ON `pembayaran` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pembayaran tidak boleh dihapus, ubah status menjadi Dibatalkan';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `pendaftaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pendaftaran` (
  `id_pendaftaran` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `status` enum('Diajukan','Diverifikasi','Diterima','Ditolak') NOT NULL DEFAULT 'Diajukan',
  `catatan_verifikasi` varchar(255) DEFAULT NULL,
  `id_user_verifikator` int DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pendaftaran`),
  UNIQUE KEY `uq_pendaftaran` (`id_siswa`,`id_tahun_ajaran`),
  KEY `idx_pendaftaran_ta` (`id_tahun_ajaran`,`kelompok`,`status`),
  KEY `fk_pendaftaran_verif` (`id_user_verifikator`),
  CONSTRAINT `fk_pendaftaran_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pendaftaran_ta` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pendaftaran_verif` FOREIGN KEY (`id_user_verifikator`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pendaftaran_bu` BEFORE UPDATE ON `pendaftaran` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `pengumpulan_tugas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengumpulan_tugas` (
  `id_pengumpulan` int NOT NULL AUTO_INCREMENT,
  `id_tugas` int NOT NULL,
  `id_rombel` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `catatan_ortu` varchar(255) DEFAULT NULL,
  `dikumpulkan_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nilai` enum('BB','MB','BSH','BSB') DEFAULT NULL,
  `umpan_balik` text,
  `id_guru_penilai` int DEFAULT NULL,
  `dinilai_pada` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengumpulan`),
  UNIQUE KEY `uq_pengumpulan` (`id_tugas`,`id_rombel`),
  KEY `fk_pengumpulan_rombel` (`id_rombel`),
  KEY `fk_pengumpulan_guru` (`id_guru_penilai`),
  CONSTRAINT `fk_pengumpulan_guru` FOREIGN KEY (`id_guru_penilai`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pengumpulan_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pengumpulan_tugas` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pengumpulan_bi` BEFORE INSERT ON `pengumpulan_tugas` FOR EACH ROW BEGIN
  DECLARE v_kelas_tugas INT;
  DECLARE v_kelas_rombel INT;
  SELECT id_kelas INTO v_kelas_tugas FROM tugas WHERE id_tugas = NEW.id_tugas;
  SELECT id_kelas INTO v_kelas_rombel FROM rombel_siswa WHERE id_rombel = NEW.id_rombel;
  IF v_kelas_tugas IS NULL OR v_kelas_rombel IS NULL OR v_kelas_tugas <> v_kelas_rombel THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa bukan anggota kelas pemilik tugas ini';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pengumpulan_bu` BEFORE UPDATE ON `pengumpulan_tugas` FOR EACH ROW BEGIN
  IF NEW.nilai IS NOT NULL AND (OLD.nilai IS NULL OR NEW.nilai <> OLD.nilai) THEN
    IF NEW.id_guru_penilai IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Guru penilai wajib diisi saat memberi nilai';
    END IF;
    SET NEW.dinilai_pada = NOW();
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `portofolio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portofolio` (
  `id_portofolio` int NOT NULL AUTO_INCREMENT,
  `id_rombel` int NOT NULL,
  `tanggal` date NOT NULL,
  `foto` varchar(255) NOT NULL,
  `narasi` text NOT NULL,
  `id_guru` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_portofolio`),
  KEY `idx_portofolio` (`id_rombel`,`tanggal`),
  KEY `fk_porto_guru` (`id_guru`),
  CONSTRAINT `fk_porto_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_porto_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rapor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_rapor_bu` BEFORE UPDATE ON `rapor` FOR EACH ROW BEGIN
  -- rapor Final terkunci; hanya refleksi orang tua yang boleh diisi. Ubah status ke Draft untuk mengedit.
  IF OLD.status = 'Final' AND NEW.status = 'Final' AND NOT (
       NEW.tanggal_rapor <=> OLD.tanggal_rapor AND NEW.tinggi_badan <=> OLD.tinggi_badan AND NEW.berat_badan <=> OLD.berat_badan
       AND NEW.narasi_agama_budi_pekerti <=> OLD.narasi_agama_budi_pekerti AND NEW.narasi_jati_diri <=> OLD.narasi_jati_diri
       AND NEW.narasi_literasi_steam <=> OLD.narasi_literasi_steam AND NEW.narasi_p5 <=> OLD.narasi_p5
       AND NEW.id_guru_kelas <=> OLD.id_guru_kelas AND NEW.id_guru_iqro <=> OLD.id_guru_iqro) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rapor Final terkunci; ubah status ke Draft untuk mengedit';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `rencana_pembelajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rencana_pembelajaran` (
  `id_rencana` int NOT NULL AUTO_INCREMENT,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `jenis` enum('RPPM','RPPH','Modul Ajar') NOT NULL DEFAULT 'RPPH',
  `tanggal` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `tema` varchar(150) NOT NULL,
  `subtema` varchar(150) DEFAULT NULL,
  `tujuan` text,
  `kegiatan` text,
  `alat_bahan` text,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Final') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rencana`),
  KEY `idx_rencana` (`id_kelas`,`tanggal`),
  KEY `fk_rencana_guru` (`id_guru`),
  CONSTRAINT `fk_rencana_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rencana_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rombel_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rombel_siswa` (
  `id_rombel` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_kelas` int NOT NULL,
  `status` enum('Aktif','Selesai','Pindah','Keluar') NOT NULL DEFAULT 'Aktif',
  `id_diskon` int DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rombel`),
  UNIQUE KEY `uq_rombel` (`id_siswa`,`id_kelas`),
  KEY `idx_rombel_kelas_status` (`id_kelas`,`status`),
  KEY `fk_rombel_diskon` (`id_diskon`),
  CONSTRAINT `fk_rombel_diskon` FOREIGN KEY (`id_diskon`) REFERENCES `diskon` (`id_diskon`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rombel_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_rombel_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_rombel_bi` BEFORE INSERT ON `rombel_siswa` FOR EACH ROW BEGIN
  CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, NULL);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_rombel_bu` BEFORE UPDATE ON `rombel_siswa` FOR EACH ROW BEGIN
  IF NEW.id_siswa <> OLD.id_siswa OR NEW.id_kelas <> OLD.id_kelas THEN
    CALL sp_validasi_rombel(NEW.id_siswa, NEW.id_kelas, OLD.id_rombel);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL AUTO_INCREMENT,
  `id_keluarga` int NOT NULL,
  `alamat_domisili` text,
  `no_induk` varchar(30) DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `nisn` varchar(10) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nama_panggilan` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') NOT NULL,
  `agama` varchar(20) NOT NULL DEFAULT 'Islam',
  `anak_ke` tinyint DEFAULT NULL,
  `kewarganegaraan` varchar(20) NOT NULL DEFAULT 'WNI',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `uq_siswa_induk` (`no_induk`),
  UNIQUE KEY `uq_siswa_nik` (`nik`),
  UNIQUE KEY `uq_siswa_nisn` (`nisn`),
  KEY `idx_siswa_nama` (`nama_lengkap`),
  KEY `idx_siswa_keluarga` (`id_keluarga`),
  CONSTRAINT `fk_siswa_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_siswa_bi` BEFORE INSERT ON `siswa` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.nama_panggilan IS NOT NULL THEN SET NEW.nama_panggilan = fn_title_case(NEW.nama_panggilan); END IF;
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_siswa_bu` BEFORE UPDATE ON `siswa` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.nama_panggilan IS NOT NULL THEN SET NEW.nama_panggilan = fn_title_case(NEW.nama_panggilan); END IF;
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_siswa_au` AFTER UPDATE ON `siswa` FOR EACH ROW BEGIN
  INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
  VALUES ('siswa', OLD.id_siswa, 'UPDATE',
    JSON_OBJECT('nama_lengkap', OLD.nama_lengkap, 'nik', OLD.nik, 'nisn', OLD.nisn, 'tanggal_lahir', OLD.tanggal_lahir, 'id_keluarga', OLD.id_keluarga),
    JSON_OBJECT('nama_lengkap', NEW.nama_lengkap, 'nik', NEW.nik, 'nisn', NEW.nisn, 'tanggal_lahir', NEW.tanggal_lahir, 'id_keluarga', NEW.id_keluarga),
    CURRENT_USER(), @app_user_id);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_siswa_ad` AFTER DELETE ON `siswa` FOR EACH ROW BEGIN
  INSERT INTO audit_log (tabel, id_record, aksi, data_lama, db_user, app_user_id)
  VALUES ('siswa', OLD.id_siswa, 'DELETE',
    JSON_OBJECT('nama_lengkap', OLD.nama_lengkap, 'nik', OLD.nik, 'nisn', OLD.nisn, 'tanggal_lahir', OLD.tanggal_lahir, 'id_keluarga', OLD.id_keluarga),
    CURRENT_USER(), @app_user_id);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `tagihan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tagihan` (
  `id_tagihan` int NOT NULL AUTO_INCREMENT,
  `id_rombel` int NOT NULL,
  `id_jenis_biaya` int NOT NULL,
  `periode` date DEFAULT NULL,
  `id_kegiatan` int DEFAULT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `diskon` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_tagihan` decimal(12,2) GENERATED ALWAYS AS ((`nominal` - `diskon`)) STORED,
  `jatuh_tempo` date NOT NULL,
  `kunci` varchar(40) GENERATED ALWAYS AS (concat(`id_jenis_biaya`,_utf8mb4'|',ifnull(((year(`periode`) * 100) + month(`periode`)),_utf8mb4''),_utf8mb4'|',ifnull(`id_kegiatan`,_utf8mb4''))) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tagihan`),
  UNIQUE KEY `uq_tagihan` (`id_rombel`,`kunci`),
  KEY `idx_tagihan_tempo` (`jatuh_tempo`),
  KEY `fk_tagihan_jenis` (`id_jenis_biaya`),
  KEY `fk_tagihan_kegiatan` (`id_kegiatan`),
  CONSTRAINT `fk_tagihan_jenis` FOREIGN KEY (`id_jenis_biaya`) REFERENCES `jenis_biaya` (`id_jenis_biaya`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tagihan_kegiatan` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tagihan_rombel` FOREIGN KEY (`id_rombel`) REFERENCES `rombel_siswa` (`id_rombel`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_tagihan_bi` BEFORE INSERT ON `tagihan` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_tagihan_bu` BEFORE UPDATE ON `tagihan` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal OR NEW.diskon <> OLD.diskon THEN
    IF fn_dibayar_tagihan(OLD.id_tagihan) > (NEW.nominal - NEW.diskon) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Total tagihan baru lebih kecil dari pembayaran yang sudah masuk';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_tagihan_au` AFTER UPDATE ON `tagihan` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal OR NEW.diskon <> OLD.diskon OR NEW.jatuh_tempo <> OLD.jatuh_tempo THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('tagihan', OLD.id_tagihan, 'UPDATE',
      JSON_OBJECT('nominal', OLD.nominal, 'diskon', OLD.diskon, 'jatuh_tempo', OLD.jatuh_tempo),
      JSON_OBJECT('nominal', NEW.nominal, 'diskon', NEW.diskon, 'jatuh_tempo', NEW.jatuh_tempo), CURRENT_USER(), @app_user_id);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_tagihan_bd` BEFORE DELETE ON `tagihan` FOR EACH ROW BEGIN
  IF EXISTS (SELECT 1 FROM alokasi_pembayaran WHERE id_tagihan = OLD.id_tagihan) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tagihan yang sudah memiliki pembayaran tidak boleh dihapus';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `tahun_ajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(9) NOT NULL,
  `tahun_mulai` smallint NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '0',
  `aktif_unik` tinyint GENERATED ALWAYS AS (if((`is_aktif` = 1),1,NULL)) VIRTUAL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tahun_ajaran`),
  UNIQUE KEY `uq_ta_nama` (`nama`),
  UNIQUE KEY `uq_ta_aktif` (`aktif_unik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarif`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarif` (
  `id_tarif` int NOT NULL AUTO_INCREMENT,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `id_jenis_biaya` int NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarif`),
  UNIQUE KEY `uq_tarif` (`id_tahun_ajaran`,`kelompok`,`id_jenis_biaya`),
  KEY `fk_tarif_jenis` (`id_jenis_biaya`),
  CONSTRAINT `fk_tarif_jenis` FOREIGN KEY (`id_jenis_biaya`) REFERENCES `jenis_biaya` (`id_jenis_biaya`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tarif_ta` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_tarif_au` AFTER UPDATE ON `tarif` FOR EACH ROW BEGIN
  IF NEW.nominal <> OLD.nominal THEN
    INSERT INTO audit_log (tabel, id_record, aksi, data_lama, data_baru, db_user, app_user_id)
    VALUES ('tarif', OLD.id_tarif, 'UPDATE', JSON_OBJECT('nominal', OLD.nominal), JSON_OBJECT('nominal', NEW.nominal), CURRENT_USER(), @app_user_id);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `transaksi_tabungan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi_tabungan` (
  `id_transaksi` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `jenis` enum('Setor','Tarik') NOT NULL,
  `jumlah` decimal(14,2) NOT NULL,
  `metode` enum('Tunai','Transfer') NOT NULL DEFAULT 'Tunai',
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `idx_ttab` (`id_siswa`,`tanggal`),
  KEY `fk_ttab_user` (`id_user_pencatat`),
  CONSTRAINT `fk_ttab_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ttab_user` FOREIGN KEY (`id_user_pencatat`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_ttab_bi` BEFORE INSERT ON `transaksi_tabungan` FOR EACH ROW BEGIN
  DECLARE v_siswa INT;
  SELECT id_siswa INTO v_siswa FROM siswa WHERE id_siswa = NEW.id_siswa FOR UPDATE;
  IF v_siswa IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa tidak ditemukan';
  END IF;
  IF NEW.jenis = 'Tarik' AND fn_saldo_tabungan(NEW.id_siswa) < NEW.jumlah THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo tabungan tidak mencukupi';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_ttab_bu` BEFORE UPDATE ON `transaksi_tabungan` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh diubah, buat transaksi koreksi';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_ttab_bd` BEFORE DELETE ON `transaksi_tabungan` FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tabungan tidak boleh dihapus, buat transaksi koreksi';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `tugas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tugas` (
  `id_tugas` int NOT NULL AUTO_INCREMENT,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `judul` varchar(150) NOT NULL,
  `kategori` varchar(30) DEFAULT NULL,
  `deskripsi` text,
  `tanggal_diberikan` date NOT NULL,
  `tenggat` date NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tugas`),
  KEY `idx_tugas` (`id_kelas`,`tenggat`),
  KEY `fk_tugas_guru` (`id_guru`),
  CONSTRAINT `fk_tugas_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tugas_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `role` enum('admin','kepala_sekolah','guru','orang_tua') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v_dashboard_kepala_sekolah`;
/*!50001 DROP VIEW IF EXISTS `v_dashboard_kepala_sekolah`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_dashboard_kepala_sekolah` AS SELECT 
 1 AS `siswa_aktif`,
 1 AS `pendaftar_menunggu`,
 1 AS `total_tunggakan`,
 1 AS `penerimaan_bulan_ini`,
 1 AS `persen_hadir_hari_ini`,
 1 AS `total_saldo_tabungan`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_guru_lengkap`;
/*!50001 DROP VIEW IF EXISTS `v_guru_lengkap`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_guru_lengkap` AS SELECT 
 1 AS `id_guru`,
 1 AS `id_user`,
 1 AS `nuptk`,
 1 AS `nik`,
 1 AS `nama_dengan_gelar`,
 1 AS `nama_lengkap`,
 1 AS `jenis_kelamin`,
 1 AS `tempat_lahir`,
 1 AS `tanggal_lahir`,
 1 AS `alamat`,
 1 AS `no_hp`,
 1 AS `email`,
 1 AS `pendidikan`,
 1 AS `jabatan`,
 1 AS `status_kepegawaian`,
 1 AS `tanggal_mulai`,
 1 AS `masa_kerja_tahun`,
 1 AS `status`,
 1 AS `foto`,
 1 AS `kelas_diampu`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_kandidat_duplikat_siswa`;
/*!50001 DROP VIEW IF EXISTS `v_kandidat_duplikat_siswa`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_kandidat_duplikat_siswa` AS SELECT 
 1 AS `nama_lengkap`,
 1 AS `tanggal_lahir`,
 1 AS `jumlah`,
 1 AS `daftar_id_siswa`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_keluarga_lengkap`;
/*!50001 DROP VIEW IF EXISTS `v_keluarga_lengkap`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_keluarga_lengkap` AS SELECT 
 1 AS `id_keluarga`,
 1 AS `id_user`,
 1 AS `no_kk`,
 1 AS `alamat`,
 1 AS `nama_ayah`,
 1 AS `nama_ibu`,
 1 AS `no_hp_utama`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_kesiapan_data`;
/*!50001 DROP VIEW IF EXISTS `v_kesiapan_data`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_kesiapan_data` AS SELECT 
 1 AS `komponen`,
 1 AS `terisi`,
 1 AS `dibutuhkan`,
 1 AS `status`,
 1 AS `keterangan`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_kualitas_data`;
/*!50001 DROP VIEW IF EXISTS `v_kualitas_data`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_kualitas_data` AS SELECT 
 1 AS `entitas`,
 1 AS `id_record`,
 1 AS `nama`,
 1 AS `masalah`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_laporan_kegiatan`;
/*!50001 DROP VIEW IF EXISTS `v_laporan_kegiatan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_laporan_kegiatan` AS SELECT 
 1 AS `id_kegiatan`,
 1 AS `nama_kegiatan`,
 1 AS `jenis`,
 1 AS `tanggal`,
 1 AS `lokasi`,
 1 AS `biaya`,
 1 AS `seluruh_sekolah`,
 1 AS `kelas_terlibat`,
 1 AS `laporan_sebelum`,
 1 AS `laporan_selama`,
 1 AS `laporan_sesudah`,
 1 AS `tagihan_terbit`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_pembayaran_detail`;
/*!50001 DROP VIEW IF EXISTS `v_pembayaran_detail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_pembayaran_detail` AS SELECT 
 1 AS `id_pembayaran`,
 1 AS `no_kuitansi`,
 1 AS `tanggal_bayar`,
 1 AS `metode`,
 1 AS `status`,
 1 AS `jumlah_tagihan`,
 1 AS `total_bayar`,
 1 AS `bukti_transfer`,
 1 AS `id_user_pencatat`,
 1 AS `id_user_verifikator`,
 1 AS `tanggal_verifikasi`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_pendaftaran_detail`;
/*!50001 DROP VIEW IF EXISTS `v_pendaftaran_detail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_pendaftaran_detail` AS SELECT 
 1 AS `id_pendaftaran`,
 1 AS `no_pendaftaran`,
 1 AS `status`,
 1 AS `tahun_ajaran`,
 1 AS `kelompok_tujuan`,
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `tanggal_lahir`,
 1 AS `nama_ayah`,
 1 AS `nama_ibu`,
 1 AS `no_hp_utama`,
 1 AS `dokumen_diunggah`,
 1 AS `dokumen_valid`,
 1 AS `catatan_verifikasi`,
 1 AS `tanggal_verifikasi`,
 1 AS `tanggal_daftar`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_pengumpulan_detail`;
/*!50001 DROP VIEW IF EXISTS `v_pengumpulan_detail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_pengumpulan_detail` AS SELECT 
 1 AS `id_pengumpulan`,
 1 AS `id_tugas`,
 1 AS `judul`,
 1 AS `id_kelas`,
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `dikumpulkan_pada`,
 1 AS `tenggat`,
 1 AS `status`,
 1 AS `nilai`,
 1 AS `umpan_balik`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_rapor_iqro`;
/*!50001 DROP VIEW IF EXISTS `v_rapor_iqro`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_rapor_iqro` AS SELECT 
 1 AS `id_rombel`,
 1 AS `id_siswa`,
 1 AS `id_indikator`,
 1 AS `kategori`,
 1 AS `indikator`,
 1 AS `urutan`,
 1 AS `semester_1`,
 1 AS `semester_2`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_rapor_lengkap`;
/*!50001 DROP VIEW IF EXISTS `v_rapor_lengkap`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_rapor_lengkap` AS SELECT 
 1 AS `id_rapor`,
 1 AS `id_rombel`,
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `nama_kelas`,
 1 AS `kelompok`,
 1 AS `tahun_ajaran`,
 1 AS `semester`,
 1 AS `status`,
 1 AS `tanggal_rapor`,
 1 AS `tinggi_badan`,
 1 AS `berat_badan`,
 1 AS `sakit`,
 1 AS `izin`,
 1 AS `tanpa_keterangan`,
 1 AS `narasi_agama_budi_pekerti`,
 1 AS `narasi_jati_diri`,
 1 AS `narasi_literasi_steam`,
 1 AS `narasi_p5`,
 1 AS `refleksi_orang_tua`,
 1 AS `guru_kelas`,
 1 AS `guru_iqro`,
 1 AS `kepala_sekolah`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_rekap_kehadiran_bulanan`;
/*!50001 DROP VIEW IF EXISTS `v_rekap_kehadiran_bulanan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_rekap_kehadiran_bulanan` AS SELECT 
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `id_kelas`,
 1 AS `nama_kelas`,
 1 AS `tahun`,
 1 AS `bulan`,
 1 AS `hadir`,
 1 AS `izin`,
 1 AS `sakit`,
 1 AS `alfa`,
 1 AS `total_hari`,
 1 AS `persen_hadir`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_rekap_pembayaran_bulanan`;
/*!50001 DROP VIEW IF EXISTS `v_rekap_pembayaran_bulanan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_rekap_pembayaran_bulanan` AS SELECT 
 1 AS `bulan`,
 1 AS `jenis_biaya`,
 1 AS `metode`,
 1 AS `jumlah_kuitansi`,
 1 AS `total_diterima`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_rekap_pendaftar`;
/*!50001 DROP VIEW IF EXISTS `v_rekap_pendaftar`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_rekap_pendaftar` AS SELECT 
 1 AS `tahun_ajaran`,
 1 AS `kelompok`,
 1 AS `total_pendaftar`,
 1 AS `diajukan`,
 1 AS `diverifikasi`,
 1 AS `diterima`,
 1 AS `ditolak`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_saldo_tabungan`;
/*!50001 DROP VIEW IF EXISTS `v_saldo_tabungan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_saldo_tabungan` AS SELECT 
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `saldo`,
 1 AS `jumlah_transaksi`,
 1 AS `transaksi_terakhir`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_siswa_guru`;
/*!50001 DROP VIEW IF EXISTS `v_siswa_guru`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_siswa_guru` AS SELECT 
 1 AS `id_siswa`,
 1 AS `id_rombel`,
 1 AS `id_kelas`,
 1 AS `nama_kelas`,
 1 AS `tahun_ajaran`,
 1 AS `kelompok`,
 1 AS `status_rombel`,
 1 AS `nama_lengkap`,
 1 AS `nama_panggilan`,
 1 AS `jenis_kelamin`,
 1 AS `tanggal_lahir`,
 1 AS `usia`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_siswa_lengkap`;
/*!50001 DROP VIEW IF EXISTS `v_siswa_lengkap`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_siswa_lengkap` AS SELECT 
 1 AS `id_siswa`,
 1 AS `id_rombel`,
 1 AS `id_kelas`,
 1 AS `id_tahun_ajaran`,
 1 AS `tahun_ajaran`,
 1 AS `kelompok`,
 1 AS `nama_kelas`,
 1 AS `status_rombel`,
 1 AS `no_induk`,
 1 AS `nik`,
 1 AS `nisn`,
 1 AS `nama_siswa`,
 1 AS `nama_panggilan`,
 1 AS `tempat_lahir_siswa`,
 1 AS `tanggal_lahir_siswa`,
 1 AS `jenis_kelamin`,
 1 AS `agama`,
 1 AS `anak_ke`,
 1 AS `kewarganegaraan`,
 1 AS `alamat_siswa`,
 1 AS `id_keluarga`,
 1 AS `nama_ayah`,
 1 AS `nama_ibu`,
 1 AS `no_hp`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_siswa_terlindungi`;
/*!50001 DROP VIEW IF EXISTS `v_siswa_terlindungi`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_siswa_terlindungi` AS SELECT 
 1 AS `id_siswa`,
 1 AS `nama_lengkap`,
 1 AS `jenis_kelamin`,
 1 AS `tanggal_lahir`,
 1 AS `nik_samar`,
 1 AS `nisn_samar`,
 1 AS `nama_kelas`,
 1 AS `tahun_ajaran`,
 1 AS `kelompok`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_status_pengumpulan_tugas`;
/*!50001 DROP VIEW IF EXISTS `v_status_pengumpulan_tugas`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_status_pengumpulan_tugas` AS SELECT 
 1 AS `id_tugas`,
 1 AS `judul`,
 1 AS `id_kelas`,
 1 AS `nama_kelas`,
 1 AS `tenggat`,
 1 AS `total_siswa`,
 1 AS `sudah_mengumpulkan`,
 1 AS `terlambat`,
 1 AS `sudah_dinilai`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_tagihan_detail`;
/*!50001 DROP VIEW IF EXISTS `v_tagihan_detail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_tagihan_detail` AS SELECT 
 1 AS `id_tagihan`,
 1 AS `id_rombel`,
 1 AS `id_siswa`,
 1 AS `id_keluarga`,
 1 AS `nama_lengkap`,
 1 AS `nama_kelas`,
 1 AS `jenis_biaya`,
 1 AS `kategori`,
 1 AS `periode`,
 1 AS `nama_kegiatan`,
 1 AS `total_tagihan`,
 1 AS `dibayar`,
 1 AS `sisa`,
 1 AS `jatuh_tempo`,
 1 AS `status_bayar`,
 1 AS `hari_terlambat`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_tunggakan`;
/*!50001 DROP VIEW IF EXISTS `v_tunggakan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_tunggakan` AS SELECT 
 1 AS `id_siswa`,
 1 AS `id_keluarga`,
 1 AS `nama_lengkap`,
 1 AS `nama_kelas`,
 1 AS `jumlah_tagihan_menunggak`,
 1 AS `total_tunggakan`,
 1 AS `jatuh_tempo_tertua`,
 1 AS `keterlambatan_terlama_hari`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `wali`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wali` (
  `id_wali` int NOT NULL AUTO_INCREMENT,
  `id_keluarga` int NOT NULL,
  `hubungan` enum('Ayah','Ibu','Wali') NOT NULL,
  `gelar_depan` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `gelar_belakang` varchar(50) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `pendidikan` varchar(30) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_wali`),
  UNIQUE KEY `uq_wali_hubungan` (`id_keluarga`,`hubungan`),
  KEY `idx_wali_nama` (`nama_lengkap`),
  CONSTRAINT `fk_wali_keluarga` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_wali_bi` BEFORE INSERT ON `wali` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NEW.tanggal_lahir IS NOT NULL AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_wali_bu` BEFORE UPDATE ON `wali` FOR EACH ROW BEGIN
  SET NEW.nama_lengkap = fn_title_case(NEW.nama_lengkap);
  IF NOT (NEW.tanggal_lahir <=> OLD.tanggal_lahir) AND NEW.tanggal_lahir > CURDATE() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tanggal lahir tidak boleh di masa depan';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_akhir_semester` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_akhir_semester`(`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS date
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-12-31') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-06-30') AS DATE));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_awal_semester` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_awal_semester`(`p_tahun_mulai` SMALLINT, `p_semester` VARCHAR(6)) RETURNS date
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN IF(p_semester = 'Ganjil', CAST(CONCAT(p_tahun_mulai, '-07-01') AS DATE), CAST(CONCAT(p_tahun_mulai + 1, '-01-01') AS DATE));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_dibayar_tagihan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_dibayar_tagihan`(`p_id_tagihan` INT) RETURNS decimal(12,2)
    READS SQL DATA
BEGIN
  DECLARE v DECIMAL(12,2);
  SELECT COALESCE(SUM(a.jumlah), 0) INTO v
    FROM alokasi_pembayaran a JOIN pembayaran p ON p.id_pembayaran = a.id_pembayaran
   WHERE a.id_tagihan = p_id_tagihan AND p.status = 'Terverifikasi';
  RETURN v;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_hitung_usia` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_hitung_usia`(`p_tanggal_lahir` DATE) RETURNS varchar(30) CHARSET utf8mb4
    NO SQL
BEGIN
  DECLARE v_bulan INT;
  IF p_tanggal_lahir IS NULL THEN RETURN NULL; END IF;
  SET v_bulan = TIMESTAMPDIFF(MONTH, p_tanggal_lahir, CURDATE());
  RETURN CONCAT(FLOOR(v_bulan / 12), ' tahun ', MOD(v_bulan, 12), ' bulan');
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_no_kuitansi` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_no_kuitansi`(`p_id` INT, `p_tanggal` DATETIME) RETURNS varchar(20) CHARSET utf8mb4
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN CONCAT('KWT-', YEAR(p_tanggal), '-', LPAD(p_id, 5, '0'));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_no_pendaftaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_no_pendaftaran`(`p_id` INT, `p_tahun_mulai` SMALLINT) RETURNS varchar(20) CHARSET utf8mb4
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN CONCAT('PPDB-', p_tahun_mulai, '-', LPAD(p_id, 4, '0'));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_persen_kehadiran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_persen_kehadiran`(`p_id_siswa` INT, `p_dari` DATE, `p_sampai` DATE) RETURNS decimal(5,2)
    READS SQL DATA
BEGIN
  DECLARE v DECIMAL(5,2);
  SELECT ROUND(100 * SUM(a.status = 'Hadir') / NULLIF(COUNT(*), 0), 2) INTO v
    FROM absensi a JOIN rombel_siswa r ON r.id_rombel = a.id_rombel
   WHERE r.id_siswa = p_id_siswa AND a.tanggal BETWEEN p_dari AND p_sampai;
  RETURN v;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_saldo_tabungan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_saldo_tabungan`(`p_id_siswa` INT) RETURNS decimal(14,2)
    READS SQL DATA
BEGIN
  DECLARE v DECIMAL(14,2);
  SELECT COALESCE(SUM(IF(jenis = 'Setor', jumlah, -jumlah)), 0) INTO v FROM transaksi_tabungan WHERE id_siswa = p_id_siswa;
  RETURN v;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_semester` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_semester`(`p_tanggal` DATE) RETURNS varchar(6) CHARSET utf8mb4
    NO SQL
    DETERMINISTIC
BEGIN
  RETURN IF(MONTH(p_tanggal) >= 7, 'Ganjil', 'Genap');
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_sisa_tagihan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_sisa_tagihan`(`p_id_tagihan` INT) RETURNS decimal(12,2)
    READS SQL DATA
BEGIN
  DECLARE v_total DECIMAL(12,2);
  SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
  IF v_total IS NULL THEN RETURN 0; END IF;
  RETURN v_total - fn_dibayar_tagihan(p_id_tagihan);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_status_tagihan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_status_tagihan`(`p_id_tagihan` INT) RETURNS varchar(12) CHARSET utf8mb4
    READS SQL DATA
BEGIN
  DECLARE v_total   DECIMAL(12,2);
  DECLARE v_dibayar DECIMAL(12,2);
  SELECT total_tagihan INTO v_total FROM tagihan WHERE id_tagihan = p_id_tagihan;
  IF v_total IS NULL THEN RETURN NULL; END IF;
  SET v_dibayar = fn_dibayar_tagihan(p_id_tagihan);
  RETURN IF(v_dibayar >= v_total, 'Lunas', IF(v_dibayar > 0, 'Sebagian', 'Belum Bayar'));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_title_case` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_title_case`(`p_teks` VARCHAR(255)) RETURNS varchar(255) CHARSET utf8mb4
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_total_tunggakan_keluarga` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_total_tunggakan_keluarga`(`p_id_keluarga` INT) RETURNS decimal(14,2)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_total_tunggakan_siswa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_total_tunggakan_siswa`(`p_id_siswa` INT) RETURNS decimal(14,2)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_batalkan_pembayaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_batalkan_pembayaran`(IN `p_id_pembayaran` INT, IN `p_id_user` INT, IN `p_alasan` VARCHAR(255))
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_bayar_tagihan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_bayar_tagihan`(IN `p_id_tagihan` INT, IN `p_jumlah` DECIMAL(12,2), IN `p_metode` VARCHAR(10), IN `p_bukti` VARCHAR(255), IN `p_id_user` INT)
BEGIN
  CALL sp_catat_pembayaran(p_metode, p_bukti, p_id_user, JSON_ARRAY(JSON_OBJECT('id_tagihan', p_id_tagihan, 'jumlah', p_jumlah)));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_buat_tagihan_bulanan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_buat_tagihan_bulanan`(IN `p_id_tahun_ajaran` INT, IN `p_periode` DATE, IN `p_jatuh_tempo` DATE)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_buat_tagihan_kegiatan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_buat_tagihan_kegiatan`(IN `p_id_kegiatan` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_buat_tagihan_pendaftaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_buat_tagihan_pendaftaran`(IN `p_id_rombel` INT, IN `p_jatuh_tempo` DATE, OUT `p_dibuat` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_catat_pembayaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_catat_pembayaran`(IN `p_metode` VARCHAR(10), IN `p_bukti` VARCHAR(255), IN `p_id_user` INT, IN `p_alokasi` JSON)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_gabung_keluarga` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_gabung_keluarga`(IN `p_id_pertahankan` INT, IN `p_id_duplikat` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_gabung_siswa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_gabung_siswa`(IN `p_id_pertahankan` INT, IN `p_id_duplikat` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_inisialisasi_absensi` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_inisialisasi_absensi`(IN `p_id_kelas` INT, IN `p_tanggal` DATE, IN `p_id_user` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_laporan_keuangan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_laporan_keuangan`(IN `p_dari` DATE, IN `p_sampai` DATE)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_portal_ringkasan_anak` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_portal_ringkasan_anak`(IN `p_id_user` INT)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_rekap_kehadiran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_rekap_kehadiran`(IN `p_id_kelas` INT, IN `p_bulan` TINYINT, IN `p_tahun` SMALLINT)
BEGIN
  SELECT * FROM v_rekap_kehadiran_bulanan WHERE id_kelas = p_id_kelas AND bulan = p_bulan AND tahun = p_tahun ORDER BY nama_lengkap;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_terima_pendaftaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_terima_pendaftaran`(IN `p_id_pendaftaran` INT, IN `p_id_kelas` INT, IN `p_id_user` INT, IN `p_jatuh_tempo` DATE)
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_transaksi_tabungan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_transaksi_tabungan`(IN `p_id_siswa` INT, IN `p_jenis` VARCHAR(10), IN `p_jumlah` DECIMAL(14,2), IN `p_metode` VARCHAR(10), IN `p_keterangan` VARCHAR(255), IN `p_id_user` INT)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  START TRANSACTION;
    INSERT INTO transaksi_tabungan (id_siswa, jenis, jumlah, metode, keterangan, id_user_pencatat)
    VALUES (p_id_siswa, p_jenis, p_jumlah, p_metode, p_keterangan, p_id_user);
  COMMIT;
  SELECT fn_saldo_tabungan(p_id_siswa) AS saldo_terbaru;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_validasi_rombel` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_validasi_rombel`(IN `p_id_siswa` INT, IN `p_id_kelas` INT, IN `p_abaikan_rombel` INT)
BEGIN
  DECLARE v_ta INT;
  SELECT id_tahun_ajaran INTO v_ta FROM kelas WHERE id_kelas = p_id_kelas;
  IF EXISTS (SELECT 1 FROM rombel_siswa r JOIN kelas k ON k.id_kelas = r.id_kelas
             WHERE r.id_siswa = p_id_siswa AND k.id_tahun_ajaran = v_ta AND r.id_rombel <> COALESCE(p_abaikan_rombel, 0)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Siswa sudah punya kelas pada tahun ajaran tersebut';
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_verifikasi_pembayaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_verifikasi_pembayaran`(IN `p_id_pembayaran` INT, IN `p_setuju` TINYINT, IN `p_id_user` INT, IN `p_catatan` VARCHAR(255))
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_verifikasi_pendaftaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_verifikasi_pendaftaran`(IN `p_id_pendaftaran` INT, IN `p_setuju` TINYINT, IN `p_id_user` INT, IN `p_catatan` VARCHAR(255))
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50001 DROP VIEW IF EXISTS `v_dashboard_kepala_sekolah`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_dashboard_kepala_sekolah` AS select (select count(distinct `rombel_siswa`.`id_siswa`) from `rombel_siswa` where (`rombel_siswa`.`status` = 'Aktif')) AS `siswa_aktif`,(select count(0) from `pendaftaran` where (`pendaftaran`.`status` in ('Diajukan','Diverifikasi'))) AS `pendaftar_menunggu`,(select coalesce(sum(`v_tagihan_detail`.`sisa`),0) from `v_tagihan_detail` where ((`v_tagihan_detail`.`sisa` > 0) and (`v_tagihan_detail`.`jatuh_tempo` < curdate()))) AS `total_tunggakan`,(select coalesce(sum(`a`.`jumlah`),0) from (`pembayaran` `p` join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) where ((`p`.`status` = 'Terverifikasi') and (`p`.`tanggal_bayar` >= date_format(curdate(),'%Y-%m-01')) and (`p`.`tanggal_bayar` < (date_format(curdate(),'%Y-%m-01') + interval 1 month)))) AS `penerimaan_bulan_ini`,(select round(((100 * sum((`absensi`.`status` = 'Hadir'))) / nullif(count(0),0)),2) from `absensi` where (`absensi`.`tanggal` = curdate())) AS `persen_hadir_hari_ini`,(select coalesce(sum(if((`transaksi_tabungan`.`jenis` = 'Setor'),`transaksi_tabungan`.`jumlah`,-(`transaksi_tabungan`.`jumlah`))),0) from `transaksi_tabungan`) AS `total_saldo_tabungan` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_guru_lengkap`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_guru_lengkap` AS select `g`.`id_guru` AS `id_guru`,`g`.`id_user` AS `id_user`,`g`.`nuptk` AS `nuptk`,`g`.`nik` AS `nik`,trim(concat_ws(' ',`g`.`gelar_depan`,concat_ws(', ',`g`.`nama_lengkap`,`g`.`gelar_belakang`))) AS `nama_dengan_gelar`,`g`.`nama_lengkap` AS `nama_lengkap`,`g`.`jenis_kelamin` AS `jenis_kelamin`,`g`.`tempat_lahir` AS `tempat_lahir`,`g`.`tanggal_lahir` AS `tanggal_lahir`,`g`.`alamat` AS `alamat`,`g`.`no_hp` AS `no_hp`,`u`.`email` AS `email`,`g`.`pendidikan` AS `pendidikan`,`g`.`jabatan` AS `jabatan`,`g`.`status_kepegawaian` AS `status_kepegawaian`,`g`.`tanggal_mulai` AS `tanggal_mulai`,timestampdiff(YEAR,`g`.`tanggal_mulai`,curdate()) AS `masa_kerja_tahun`,`g`.`status` AS `status`,`g`.`foto` AS `foto`,(select group_concat(concat(`kl`.`nama_kelas`,' (',`gk`.`peran`,')') order by `kl`.`nama_kelas` ASC separator ', ') from ((`guru_kelas` `gk` join `kelas` `kl` on((`kl`.`id_kelas` = `gk`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`gk`.`id_guru` = `g`.`id_guru`) and (`ta`.`is_aktif` = 1))) AS `kelas_diampu` from (`guru` `g` left join `users` `u` on((`u`.`id_user` = `g`.`id_user`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_kandidat_duplikat_siswa`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_kandidat_duplikat_siswa` AS select `siswa`.`nama_lengkap` AS `nama_lengkap`,`siswa`.`tanggal_lahir` AS `tanggal_lahir`,count(0) AS `jumlah`,group_concat(`siswa`.`id_siswa` order by `siswa`.`id_siswa` ASC separator ',') AS `daftar_id_siswa` from `siswa` group by `siswa`.`nama_lengkap`,`siswa`.`tanggal_lahir` having (count(0) > 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_keluarga_lengkap`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_keluarga_lengkap` AS select `k`.`id_keluarga` AS `id_keluarga`,`k`.`id_user` AS `id_user`,`k`.`no_kk` AS `no_kk`,`k`.`alamat` AS `alamat`,trim(concat_ws(' ',`ay`.`gelar_depan`,concat_ws(', ',`ay`.`nama_lengkap`,`ay`.`gelar_belakang`))) AS `nama_ayah`,trim(concat_ws(' ',`ib`.`gelar_depan`,concat_ws(', ',`ib`.`nama_lengkap`,`ib`.`gelar_belakang`))) AS `nama_ibu`,`kk`.`nomor` AS `no_hp_utama` from (((`keluarga` `k` left join `wali` `ay` on(((`ay`.`id_keluarga` = `k`.`id_keluarga`) and (`ay`.`hubungan` = 'Ayah')))) left join `wali` `ib` on(((`ib`.`id_keluarga` = `k`.`id_keluarga`) and (`ib`.`hubungan` = 'Ibu')))) left join `kontak_keluarga` `kk` on(((`kk`.`id_keluarga` = `k`.`id_keluarga`) and (`kk`.`urutan` = 1)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_kesiapan_data`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_kesiapan_data` AS select `x`.`komponen` AS `komponen`,`x`.`terisi` AS `terisi`,`x`.`dibutuhkan` AS `dibutuhkan`,if((`x`.`terisi` >= `x`.`dibutuhkan`),'Lengkap','Belum lengkap') AS `status`,`x`.`keterangan` AS `keterangan` from (select 'Tarif SPP tahun ajaran aktif' AS `komponen`,(select count(0) from ((`tarif` `tf` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `tf`.`id_tahun_ajaran`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `tf`.`id_jenis_biaya`))) where ((`ta`.`is_aktif` = 1) and (`jb`.`kategori` = 'Bulanan'))) AS `terisi`,(select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1)) AS `dibutuhkan`,'Nominal SPP tiap kelompok' AS `keterangan` union all select 'Tarif uang pendaftaran tahun ajaran aktif' AS `Tarif uang pendaftaran tahun ajaran aktif`,(select count(0) from ((`tarif` `tf` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `tf`.`id_tahun_ajaran`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `tf`.`id_jenis_biaya`))) where ((`ta`.`is_aktif` = 1) and (`jb`.`kategori` = 'Pendaftaran'))) AS `(select count(0) from ((``tarif`` ``tf`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``tf``.``id_tahun_ajaran``))) join ``jenis_biaya`` ``jb`` on((``jb``.``id_jenis_biaya`` = ``tf``.``id_jenis_biaya``))) where ((``ta``.``is_aktif`` = 1) and (``jb``.``kategori`` = 'Pendafta`,((select count(0) from `jenis_biaya` where (`jenis_biaya`.`kategori` = 'Pendaftaran')) * (select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1))) AS `((select count(0) from ``jenis_biaya`` where (``jenis_biaya``.``kategori`` = 'Pendaftaran')) * (select count(distinct ``kl``.``kelompok``) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where (``ta``.``is_aktif`` = 1)`,'Administrasi, pembangunan, baju, buku dan perlengkapan, outbond' AS `Administrasi, pembangunan, baju, buku dan perlengkapan, outbond` union all select 'Indikator rapor Iqro per kelompok' AS `Indikator rapor Iqro per kelompok`,(select count(distinct `indikator_iqro`.`kelompok`) from `indikator_iqro`) AS `(select count(distinct ``indikator_iqro``.``kelompok``) from ``indikator_iqro``)`,(select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1)) AS `(select count(distinct ``kl``.``kelompok``) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where (``ta``.``is_aktif`` = 1))`,'KB, TK A, TK B' AS `KB, TK A, TK B` union all select 'Akun administrator TK' AS `Akun administrator TK`,(select count(0) from `users` where (`users`.`role` = 'admin')) AS `(select count(0) from ``users`` where (``users``.``role`` = 'admin'))`,1 AS `1`,'Staf pengelola data dan keuangan' AS `Staf pengelola data dan keuangan` union all select 'Akun kepala sekolah' AS `Akun kepala sekolah`,(select count(0) from `users` where (`users`.`role` = 'kepala_sekolah')) AS `(select count(0) from ``users`` where (``users``.``role`` = 'kepala_sekolah'))`,1 AS `1`,'Dibuat dari data guru' AS `Dibuat dari data guru` union all select 'Guru aktif yang punya akun' AS `Guru aktif yang punya akun`,(select count(0) from `guru` where ((`guru`.`status` = 'Aktif') and (`guru`.`id_user` is not null))) AS `(select count(0) from ``guru`` where ((``guru``.``status`` = 'Aktif') and (``guru``.``id_user`` is not null)))`,(select count(0) from `guru` where (`guru`.`status` = 'Aktif')) AS `(select count(0) from ``guru`` where (``guru``.``status`` = 'Aktif'))`,'Akun login guru' AS `Akun login guru` union all select 'Kelas aktif yang punya wali kelas' AS `Kelas aktif yang punya wali kelas`,(select count(0) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan') and exists(select 1 from `guru_kelas` `gk` where ((`gk`.`id_kelas` = `kl`.`id_kelas`) and (`gk`.`peran` = 'Wali Kelas'))))) AS `(select count(0) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where ((``ta``.``is_aktif`` = 1) and (``kl``.``nama_kelas`` <> 'Belum Ditentukan') and exists(select 1 from ``guru_kelas`` ``gk`` where ((``gk``.``id_kela`,(select count(0) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan'))) AS `(select count(0) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where ((``ta``.``is_aktif`` = 1) and (``kl``.``nama_kelas`` <> 'Belum Ditentukan')))`,'Penempatan guru ke kelas' AS `Penempatan guru ke kelas` union all select 'Siswa aktif sudah punya kelas definitif' AS `Siswa aktif sudah punya kelas definitif`,(select count(0) from (`rombel_siswa` `r` join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) where ((`r`.`status` = 'Aktif') and (`kl`.`nama_kelas` <> 'Belum Ditentukan'))) AS `(select count(0) from (``rombel_siswa`` ``r`` join ``kelas`` ``kl`` on((``kl``.``id_kelas`` = ``r``.``id_kelas``))) where ((``r``.``status`` = 'Aktif') and (``kl``.``nama_kelas`` <> 'Belum Ditentukan')))`,(select count(0) from `rombel_siswa` where (`rombel_siswa`.`status` = 'Aktif')) AS `(select count(0) from ``rombel_siswa`` where (``rombel_siswa``.``status`` = 'Aktif'))`,'Siswa yang masih di kelas Belum Ditentukan' AS `Siswa yang masih di kelas Belum Ditentukan` union all select 'Keluarga siswa aktif punya nomor HP' AS `Keluarga siswa aktif punya nomor HP`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) where exists(select 1 from `kontak_keluarga` `c` where (`c`.`id_keluarga` = `s`.`id_keluarga`))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))) where exists(select 1 from ``kontak_keluarga`` ``c`` where (``c``.``id_keluarga`` = ``s``.``id_keluarga``)))`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif'))))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))))`,'Kontak orang tua' AS `Kontak orang tua` union all select 'Keluarga siswa aktif punya akun login' AS `Keluarga siswa aktif punya akun login`,(select count(distinct `s`.`id_keluarga`) from ((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `keluarga` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) where (`f`.`id_user` is not null)) AS `(select count(distinct ``s``.``id_keluarga``) from ((``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))) join ``keluarga`` ``f`` on((``f``.``id_keluarga`` = ``s``.``id_keluarga``))) where (``f``.``id_user`` is not null))`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif'))))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))))`,'Akun orang tua untuk portal' AS `Akun orang tua untuk portal`) `x` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_kualitas_data`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_kualitas_data` AS select 'siswa' AS `entitas`,`s`.`id_siswa` AS `id_record`,`s`.`nama_lengkap` AS `nama`,'Tanggal lahir kosong' AS `masalah` from `siswa` `s` where (`s`.`tanggal_lahir` is null) union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'NIK belum diisi' AS `NIK belum diisi` from `siswa` `s` where (`s`.`nik` is null) union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'Belum punya kelas' AS `Belum punya kelas` from `siswa` `s` where exists(select 1 from `rombel_siswa` `r` where (`r`.`id_siswa` = `s`.`id_siswa`)) is false union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'Siswa aktif belum ditempatkan di kelas definitif' AS `Siswa aktif belum ditempatkan di kelas definitif` from ((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) where (`kl`.`nama_kelas` = 'Belum Ditentukan') union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Ayah/ibu belum terdata' AS `Ayah/ibu belum terdata` from `keluarga` `k` where exists(select 1 from `wali` `w` where (`w`.`id_keluarga` = `k`.`id_keluarga`)) is false union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Nomor HP belum ada' AS `Nomor HP belum ada` from `keluarga` `k` where exists(select 1 from `kontak_keluarga` `c` where (`c`.`id_keluarga` = `k`.`id_keluarga`)) is false union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Alamat belum ada' AS `Alamat belum ada` from `keluarga` `k` where (`k`.`alamat` is null) union all select 'kelas' AS `kelas`,`kl`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,'Kelas tahun ajaran aktif belum punya wali kelas' AS `Kelas tahun ajaran aktif belum punya wali kelas` from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan') and exists(select 1 from `guru_kelas` `gk` where ((`gk`.`id_kelas` = `kl`.`id_kelas`) and (`gk`.`peran` = 'Wali Kelas'))) is false) union all select 'guru' AS `guru`,`g`.`id_guru` AS `id_guru`,`g`.`nama_lengkap` AS `nama_lengkap`,'NUPTK belum diisi' AS `NUPTK belum diisi` from `guru` `g` where ((`g`.`nuptk` is null) and (`g`.`status` = 'Aktif')) union all select 'guru' AS `guru`,`g`.`id_guru` AS `id_guru`,`g`.`nama_lengkap` AS `nama_lengkap`,'Belum punya akun login' AS `Belum punya akun login` from `guru` `g` where ((`g`.`id_user` is null) and (`g`.`status` = 'Aktif')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_laporan_kegiatan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_laporan_kegiatan` AS select `k`.`id_kegiatan` AS `id_kegiatan`,`k`.`nama_kegiatan` AS `nama_kegiatan`,`k`.`jenis` AS `jenis`,`k`.`tanggal` AS `tanggal`,`k`.`lokasi` AS `lokasi`,`k`.`biaya` AS `biaya`,`k`.`seluruh_sekolah` AS `seluruh_sekolah`,(select group_concat(`kl`.`nama_kelas` order by `kl`.`nama_kelas` ASC separator ', ') from (`kegiatan_kelas` `kk` join `kelas` `kl` on((`kl`.`id_kelas` = `kk`.`id_kelas`))) where (`kk`.`id_kegiatan` = `k`.`id_kegiatan`)) AS `kelas_terlibat`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Sebelum'))) AS `laporan_sebelum`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Selama'))) AS `laporan_selama`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Sesudah'))) AS `laporan_sesudah`,(select count(0) from `tagihan` `t` where (`t`.`id_kegiatan` = `k`.`id_kegiatan`)) AS `tagihan_terbit` from `kegiatan` `k` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_pembayaran_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pembayaran_detail` AS select `p`.`id_pembayaran` AS `id_pembayaran`,`fn_no_kuitansi`(`p`.`id_pembayaran`,`p`.`tanggal_bayar`) AS `no_kuitansi`,`p`.`tanggal_bayar` AS `tanggal_bayar`,`p`.`metode` AS `metode`,`p`.`status` AS `status`,count(`a`.`id_alokasi`) AS `jumlah_tagihan`,coalesce(sum(`a`.`jumlah`),0) AS `total_bayar`,`p`.`bukti_transfer` AS `bukti_transfer`,`p`.`id_user_pencatat` AS `id_user_pencatat`,`p`.`id_user_verifikator` AS `id_user_verifikator`,`p`.`tanggal_verifikasi` AS `tanggal_verifikasi` from (`pembayaran` `p` left join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) group by `p`.`id_pembayaran`,`p`.`tanggal_bayar`,`p`.`metode`,`p`.`status`,`p`.`bukti_transfer`,`p`.`id_user_pencatat`,`p`.`id_user_verifikator`,`p`.`tanggal_verifikasi` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_pendaftaran_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pendaftaran_detail` AS select `p`.`id_pendaftaran` AS `id_pendaftaran`,`fn_no_pendaftaran`(`p`.`id_pendaftaran`,`ta`.`tahun_mulai`) AS `no_pendaftaran`,`p`.`status` AS `status`,`ta`.`nama` AS `tahun_ajaran`,`p`.`kelompok` AS `kelompok_tujuan`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`tanggal_lahir` AS `tanggal_lahir`,`f`.`nama_ayah` AS `nama_ayah`,`f`.`nama_ibu` AS `nama_ibu`,`f`.`no_hp_utama` AS `no_hp_utama`,(select count(0) from `dokumen_pendaftaran` `d` where (`d`.`id_pendaftaran` = `p`.`id_pendaftaran`)) AS `dokumen_diunggah`,(select count(0) from `dokumen_pendaftaran` `d` where ((`d`.`id_pendaftaran` = `p`.`id_pendaftaran`) and (`d`.`status_verifikasi` = 'Valid'))) AS `dokumen_valid`,`p`.`catatan_verifikasi` AS `catatan_verifikasi`,`p`.`tanggal_verifikasi` AS `tanggal_verifikasi`,`p`.`created_at` AS `tanggal_daftar` from (((`pendaftaran` `p` join `siswa` `s` on((`s`.`id_siswa` = `p`.`id_siswa`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `p`.`id_tahun_ajaran`))) left join `v_keluarga_lengkap` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_pengumpulan_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_pengumpulan_detail` AS select `pt`.`id_pengumpulan` AS `id_pengumpulan`,`pt`.`id_tugas` AS `id_tugas`,`t`.`judul` AS `judul`,`t`.`id_kelas` AS `id_kelas`,`r`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`pt`.`dikumpulkan_pada` AS `dikumpulkan_pada`,`t`.`tenggat` AS `tenggat`,(case when (`pt`.`nilai` is not null) then 'Dinilai' when (cast(`pt`.`dikumpulkan_pada` as date) > `t`.`tenggat`) then 'Terlambat' else 'Dikumpulkan' end) AS `status`,`pt`.`nilai` AS `nilai`,`pt`.`umpan_balik` AS `umpan_balik` from (((`pengumpulan_tugas` `pt` join `tugas` `t` on((`t`.`id_tugas` = `pt`.`id_tugas`))) join `rombel_siswa` `r` on((`r`.`id_rombel` = `pt`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_rapor_iqro`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_rapor_iqro` AS select `r`.`id_rombel` AS `id_rombel`,`r`.`id_siswa` AS `id_siswa`,`i`.`id_indikator` AS `id_indikator`,`i`.`kategori` AS `kategori`,`i`.`nama` AS `indikator`,`i`.`urutan` AS `urutan`,max((case when (`n`.`semester` = 'Ganjil') then `n`.`skala` end)) AS `semester_1`,max((case when (`n`.`semester` = 'Genap') then `n`.`skala` end)) AS `semester_2` from (((`rombel_siswa` `r` join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `indikator_iqro` `i` on((`i`.`kelompok` = `kl`.`kelompok`))) left join `nilai_iqro` `n` on(((`n`.`id_rombel` = `r`.`id_rombel`) and (`n`.`id_indikator` = `i`.`id_indikator`)))) group by `r`.`id_rombel`,`r`.`id_siswa`,`i`.`id_indikator`,`i`.`kategori`,`i`.`nama`,`i`.`urutan` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_rapor_lengkap`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_rapor_lengkap` AS select `rp`.`id_rapor` AS `id_rapor`,`rp`.`id_rombel` AS `id_rombel`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`kl`.`nama_kelas` AS `nama_kelas`,`kl`.`kelompok` AS `kelompok`,`ta`.`nama` AS `tahun_ajaran`,`rp`.`semester` AS `semester`,`rp`.`status` AS `status`,`rp`.`tanggal_rapor` AS `tanggal_rapor`,`rp`.`tinggi_badan` AS `tinggi_badan`,`rp`.`berat_badan` AS `berat_badan`,(select coalesce(sum((`a`.`status` = 'Sakit')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `sakit`,(select coalesce(sum((`a`.`status` = 'Izin')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `izin`,(select coalesce(sum((`a`.`status` = 'Alfa')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `tanpa_keterangan`,`rp`.`narasi_agama_budi_pekerti` AS `narasi_agama_budi_pekerti`,`rp`.`narasi_jati_diri` AS `narasi_jati_diri`,`rp`.`narasi_literasi_steam` AS `narasi_literasi_steam`,`rp`.`narasi_p5` AS `narasi_p5`,`rp`.`refleksi_orang_tua` AS `refleksi_orang_tua`,trim(concat_ws(' ',`gk`.`gelar_depan`,concat_ws(', ',`gk`.`nama_lengkap`,`gk`.`gelar_belakang`))) AS `guru_kelas`,trim(concat_ws(' ',`gi`.`gelar_depan`,concat_ws(', ',`gi`.`nama_lengkap`,`gi`.`gelar_belakang`))) AS `guru_iqro`,(select trim(concat_ws(' ',`g`.`gelar_depan`,concat_ws(', ',`g`.`nama_lengkap`,`g`.`gelar_belakang`))) from `guru` `g` where (`g`.`kepsek_unik` = 1)) AS `kepala_sekolah` from ((((((`rapor` `rp` join `rombel_siswa` `r` on((`r`.`id_rombel` = `rp`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) left join `guru` `gk` on((`gk`.`id_guru` = `rp`.`id_guru_kelas`))) left join `guru` `gi` on((`gi`.`id_guru` = `rp`.`id_guru_iqro`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_rekap_kehadiran_bulanan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_rekap_kehadiran_bulanan` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`r`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,year(`a`.`tanggal`) AS `tahun`,month(`a`.`tanggal`) AS `bulan`,sum((`a`.`status` = 'Hadir')) AS `hadir`,sum((`a`.`status` = 'Izin')) AS `izin`,sum((`a`.`status` = 'Sakit')) AS `sakit`,sum((`a`.`status` = 'Alfa')) AS `alfa`,count(0) AS `total_hari`,round(((100 * sum((`a`.`status` = 'Hadir'))) / count(0)),2) AS `persen_hadir` from (((`absensi` `a` join `rombel_siswa` `r` on((`r`.`id_rombel` = `a`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) group by `s`.`id_siswa`,`s`.`nama_lengkap`,`r`.`id_kelas`,`kl`.`nama_kelas`,year(`a`.`tanggal`),month(`a`.`tanggal`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_rekap_pembayaran_bulanan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_rekap_pembayaran_bulanan` AS select date_format(`p`.`tanggal_bayar`,'%Y-%m') AS `bulan`,`jb`.`nama` AS `jenis_biaya`,`p`.`metode` AS `metode`,count(distinct `p`.`id_pembayaran`) AS `jumlah_kuitansi`,sum(`a`.`jumlah`) AS `total_diterima` from (((`pembayaran` `p` join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) join `tagihan` `t` on((`t`.`id_tagihan` = `a`.`id_tagihan`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `t`.`id_jenis_biaya`))) where (`p`.`status` = 'Terverifikasi') group by date_format(`p`.`tanggal_bayar`,'%Y-%m'),`jb`.`nama`,`p`.`metode` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_rekap_pendaftar`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_rekap_pendaftar` AS select `ta`.`nama` AS `tahun_ajaran`,`p`.`kelompok` AS `kelompok`,count(0) AS `total_pendaftar`,sum((`p`.`status` = 'Diajukan')) AS `diajukan`,sum((`p`.`status` = 'Diverifikasi')) AS `diverifikasi`,sum((`p`.`status` = 'Diterima')) AS `diterima`,sum((`p`.`status` = 'Ditolak')) AS `ditolak` from (`pendaftaran` `p` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `p`.`id_tahun_ajaran`))) group by `ta`.`nama`,`p`.`kelompok` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_saldo_tabungan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_saldo_tabungan` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,sum(if((`t`.`jenis` = 'Setor'),`t`.`jumlah`,-(`t`.`jumlah`))) AS `saldo`,count(0) AS `jumlah_transaksi`,max(`t`.`tanggal`) AS `transaksi_terakhir` from (`siswa` `s` join `transaksi_tabungan` `t` on((`t`.`id_siswa` = `s`.`id_siswa`))) group by `s`.`id_siswa`,`s`.`nama_lengkap` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_siswa_guru`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_siswa_guru` AS select `s`.`id_siswa` AS `id_siswa`,`r`.`id_rombel` AS `id_rombel`,`kl`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok`,`r`.`status` AS `status_rombel`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`nama_panggilan` AS `nama_panggilan`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`tanggal_lahir` AS `tanggal_lahir`,`fn_hitung_usia`(`s`.`tanggal_lahir`) AS `usia` from (((`siswa` `s` join `rombel_siswa` `r` on((`r`.`id_siswa` = `s`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_siswa_lengkap`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_siswa_lengkap` AS select `s`.`id_siswa` AS `id_siswa`,`r`.`id_rombel` AS `id_rombel`,`kl`.`id_kelas` AS `id_kelas`,`ta`.`id_tahun_ajaran` AS `id_tahun_ajaran`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok`,`kl`.`nama_kelas` AS `nama_kelas`,`r`.`status` AS `status_rombel`,`s`.`no_induk` AS `no_induk`,`s`.`nik` AS `nik`,`s`.`nisn` AS `nisn`,`s`.`nama_lengkap` AS `nama_siswa`,`s`.`nama_panggilan` AS `nama_panggilan`,`s`.`tempat_lahir` AS `tempat_lahir_siswa`,`s`.`tanggal_lahir` AS `tanggal_lahir_siswa`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`agama` AS `agama`,`s`.`anak_ke` AS `anak_ke`,`s`.`kewarganegaraan` AS `kewarganegaraan`,coalesce(`s`.`alamat_domisili`,`f`.`alamat`) AS `alamat_siswa`,`f`.`id_keluarga` AS `id_keluarga`,`f`.`nama_ayah` AS `nama_ayah`,`f`.`nama_ibu` AS `nama_ibu`,`f`.`no_hp_utama` AS `no_hp` from ((((`siswa` `s` join `rombel_siswa` `r` on((`r`.`id_siswa` = `s`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) left join `v_keluarga_lengkap` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_siswa_terlindungi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_siswa_terlindungi` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`tanggal_lahir` AS `tanggal_lahir`,if((`s`.`nik` is null),NULL,concat(left(`s`.`nik`,4),repeat('*',8),right(`s`.`nik`,4))) AS `nik_samar`,if((`s`.`nisn` is null),NULL,concat(repeat('*',7),right(`s`.`nisn`,3))) AS `nisn_samar`,`kl`.`nama_kelas` AS `nama_kelas`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok` from (((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_status_pengumpulan_tugas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_status_pengumpulan_tugas` AS select `t`.`id_tugas` AS `id_tugas`,`t`.`judul` AS `judul`,`t`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,`t`.`tenggat` AS `tenggat`,(select count(0) from `rombel_siswa` `r` where ((`r`.`id_kelas` = `t`.`id_kelas`) and (`r`.`status` = 'Aktif'))) AS `total_siswa`,count(`pt`.`id_pengumpulan`) AS `sudah_mengumpulkan`,coalesce(sum((cast(`pt`.`dikumpulkan_pada` as date) > `t`.`tenggat`)),0) AS `terlambat`,coalesce(sum((`pt`.`nilai` is not null)),0) AS `sudah_dinilai` from ((`tugas` `t` join `kelas` `kl` on((`kl`.`id_kelas` = `t`.`id_kelas`))) left join `pengumpulan_tugas` `pt` on((`pt`.`id_tugas` = `t`.`id_tugas`))) group by `t`.`id_tugas`,`t`.`judul`,`t`.`id_kelas`,`kl`.`nama_kelas`,`t`.`tenggat` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_tagihan_detail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tagihan_detail` AS select `t`.`id_tagihan` AS `id_tagihan`,`r`.`id_rombel` AS `id_rombel`,`s`.`id_siswa` AS `id_siswa`,`s`.`id_keluarga` AS `id_keluarga`,`s`.`nama_lengkap` AS `nama_lengkap`,`kl`.`nama_kelas` AS `nama_kelas`,`jb`.`nama` AS `jenis_biaya`,`jb`.`kategori` AS `kategori`,`t`.`periode` AS `periode`,`kg`.`nama_kegiatan` AS `nama_kegiatan`,`t`.`total_tagihan` AS `total_tagihan`,coalesce(`d`.`dibayar`,0) AS `dibayar`,(`t`.`total_tagihan` - coalesce(`d`.`dibayar`,0)) AS `sisa`,`t`.`jatuh_tempo` AS `jatuh_tempo`,(case when (coalesce(`d`.`dibayar`,0) >= `t`.`total_tagihan`) then 'Lunas' when (coalesce(`d`.`dibayar`,0) > 0) then 'Sebagian' else 'Belum Bayar' end) AS `status_bayar`,greatest((to_days(curdate()) - to_days(`t`.`jatuh_tempo`)),0) AS `hari_terlambat` from ((((((`tagihan` `t` join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `t`.`id_jenis_biaya`))) join `rombel_siswa` `r` on((`r`.`id_rombel` = `t`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) left join `kegiatan` `kg` on((`kg`.`id_kegiatan` = `t`.`id_kegiatan`))) left join (select `a`.`id_tagihan` AS `id_tagihan`,sum(`a`.`jumlah`) AS `dibayar` from (`alokasi_pembayaran` `a` join `pembayaran` `p` on((`p`.`id_pembayaran` = `a`.`id_pembayaran`))) where (`p`.`status` = 'Terverifikasi') group by `a`.`id_tagihan`) `d` on((`d`.`id_tagihan` = `t`.`id_tagihan`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_tunggakan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_tunggakan` AS select `v_tagihan_detail`.`id_siswa` AS `id_siswa`,`v_tagihan_detail`.`id_keluarga` AS `id_keluarga`,`v_tagihan_detail`.`nama_lengkap` AS `nama_lengkap`,`v_tagihan_detail`.`nama_kelas` AS `nama_kelas`,count(0) AS `jumlah_tagihan_menunggak`,sum(`v_tagihan_detail`.`sisa`) AS `total_tunggakan`,min(`v_tagihan_detail`.`jatuh_tempo`) AS `jatuh_tempo_tertua`,max(`v_tagihan_detail`.`hari_terlambat`) AS `keterlambatan_terlama_hari` from `v_tagihan_detail` where ((`v_tagihan_detail`.`sisa` > 0) and (`v_tagihan_detail`.`jatuh_tempo` < curdate())) group by `v_tagihan_detail`.`id_siswa`,`v_tagihan_detail`.`id_keluarga`,`v_tagihan_detail`.`nama_lengkap`,`v_tagihan_detail`.`nama_kelas` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

