-- Struktur 25 tabel (tanpa index, tanpa foreign key)
-- Impor ke database db_tk_final (buat dulu databasenya, utf8mb4_0900_ai_ci). Urutan: lihat BACA_DULU.txt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `absensi` (
  `id_absensi` int NOT NULL,
  `id_rombel` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Izin','Sakit','Alfa') NOT NULL DEFAULT 'Hadir',
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `alokasi_pembayaran` (
  `id_alokasi` int NOT NULL,
  `id_pembayaran` int NOT NULL,
  `id_tagihan` int NOT NULL,
  `jumlah` decimal(12,2) NOT NULL
) ;

CREATE TABLE `audit_log` (
  `id_log` bigint NOT NULL,
  `tabel` varchar(64) NOT NULL,
  `id_record` varchar(64) NOT NULL,
  `aksi` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `data_lama` json DEFAULT NULL,
  `data_baru` json DEFAULT NULL,
  `db_user` varchar(128) DEFAULT NULL,
  `app_user_id` int DEFAULT NULL,
  `waktu` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `dokumentasi_kegiatan` (
  `id_dokumentasi` int NOT NULL,
  `id_kegiatan` int NOT NULL,
  `tahap` enum('Sebelum','Selama','Sesudah') NOT NULL DEFAULT 'Selama',
  `tipe` enum('Foto','Video','Dokumen') NOT NULL DEFAULT 'Foto',
  `file_path` varchar(255) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_upload` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `dokumen_pendaftaran` (
  `id_dokumen` int NOT NULL,
  `id_pendaftaran` int NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status_verifikasi` enum('Menunggu','Valid','Tidak Valid') NOT NULL DEFAULT 'Menunggu',
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `guru` (
  `id_guru` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `guru_kelas` (
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `peran` enum('Wali Kelas','Pendamping') NOT NULL DEFAULT 'Wali Kelas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `indikator_iqro` (
  `id_indikator` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `kategori` varchar(60) DEFAULT NULL,
  `nama` varchar(120) NOT NULL,
  `urutan` smallint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `jenis_biaya` (
  `id_jenis_biaya` int NOT NULL,
  `nama` varchar(60) NOT NULL,
  `kategori` enum('Bulanan','Pendaftaran','Kegiatan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `kegiatan` (
  `id_kegiatan` int NOT NULL,
  `nama_kegiatan` varchar(150) NOT NULL,
  `jenis` enum('Dalam Kelas','Luar Kelas','Field Trip') NOT NULL,
  `tanggal` date NOT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `deskripsi` text,
  `biaya` decimal(12,2) DEFAULT NULL,
  `seluruh_sekolah` tinyint(1) NOT NULL DEFAULT '0',
  `id_user_pembuat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `kegiatan_kelas` (
  `id_kegiatan` int NOT NULL,
  `id_kelas` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `kapasitas` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `keluarga` (
  `id_keluarga` int NOT NULL,
  `id_user` int DEFAULT NULL,
  `no_kk` varchar(16) DEFAULT NULL,
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `kontak_keluarga` (
  `id_kontak` int NOT NULL,
  `id_keluarga` int NOT NULL,
  `nomor` varchar(16) NOT NULL,
  `urutan` tinyint NOT NULL DEFAULT '1'
) ;

CREATE TABLE `nilai_iqro` (
  `id_nilai` int NOT NULL,
  `id_rombel` int NOT NULL,
  `id_indikator` int NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `skala` enum('BB','MB','BSH','BSB') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL,
  `tanggal_bayar` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metode` enum('Tunai','Transfer') NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu Verifikasi','Terverifikasi','Ditolak','Dibatalkan') NOT NULL DEFAULT 'Menunggu Verifikasi',
  `catatan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `id_user_verifikator` int DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pendaftaran` (
  `id_pendaftaran` int NOT NULL,
  `id_siswa` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `status` enum('Diajukan','Diverifikasi','Diterima','Ditolak') NOT NULL DEFAULT 'Diajukan',
  `catatan_verifikasi` varchar(255) DEFAULT NULL,
  `id_user_verifikator` int DEFAULT NULL,
  `tanggal_verifikasi` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `pengumpulan_tugas` (
  `id_pengumpulan` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `portofolio` (
  `id_portofolio` int NOT NULL,
  `id_rombel` int NOT NULL,
  `tanggal` date NOT NULL,
  `foto` varchar(255) NOT NULL,
  `narasi` text NOT NULL,
  `id_guru` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `rapor` (
  `id_rapor` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `rencana_pembelajaran` (
  `id_rencana` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `tanggal` date NOT NULL,
  `tema` varchar(150) NOT NULL,
  `subtema` varchar(150) DEFAULT NULL,
  `tujuan` text,
  `kegiatan` text,
  `alat_bahan` text,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Final') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `rombel_siswa` (
  `id_rombel` int NOT NULL,
  `id_siswa` int NOT NULL,
  `id_kelas` int NOT NULL,
  `status` enum('Aktif','Selesai','Pindah','Keluar') NOT NULL DEFAULT 'Aktif',
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `tagihan` (
  `id_tagihan` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int NOT NULL,
  `nama` varchar(9) NOT NULL,
  `tahun_mulai` smallint NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '0',
  `aktif_unik` tinyint GENERATED ALWAYS AS (if((`is_aktif` = 1),1,NULL)) VIRTUAL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `tarif` (
  `id_tarif` int NOT NULL,
  `id_tahun_ajaran` int NOT NULL,
  `kelompok` enum('KB','TK A','TK B') NOT NULL,
  `id_jenis_biaya` int NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `transaksi_tabungan` (
  `id_transaksi` int NOT NULL,
  `id_siswa` int NOT NULL,
  `jenis` enum('Setor','Tarik') NOT NULL,
  `jumlah` decimal(14,2) NOT NULL,
  `metode` enum('Tunai','Transfer') NOT NULL DEFAULT 'Tunai',
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `keterangan` varchar(255) DEFAULT NULL,
  `id_user_pencatat` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

CREATE TABLE `tugas` (
  `id_tugas` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text,
  `tanggal_diberikan` date NOT NULL,
  `tenggat` date NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ;

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `role` enum('admin','kepala_sekolah','guru','orang_tua') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `wali` (
  `id_wali` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

