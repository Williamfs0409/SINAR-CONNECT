-- Index dan primary key
-- Impor ke database db_tk_final (buat dulu databasenya, utf8mb4_0900_ai_ci). Urutan: lihat BACA_DULU.txt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD UNIQUE KEY `uq_absensi` (`id_rombel`,`tanggal`),
  ADD KEY `idx_absensi_tanggal` (`tanggal`),
  ADD KEY `fk_absensi_user` (`id_user_pencatat`);

--
-- Indexes for table `alokasi_pembayaran`
--
ALTER TABLE `alokasi_pembayaran`
  ADD PRIMARY KEY (`id_alokasi`),
  ADD UNIQUE KEY `uq_alokasi` (`id_pembayaran`,`id_tagihan`),
  ADD KEY `idx_alokasi_tagihan` (`id_tagihan`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_audit_record` (`tabel`,`id_record`),
  ADD KEY `idx_audit_waktu` (`waktu`);

--
-- Indexes for table `dokumentasi_kegiatan`
--
ALTER TABLE `dokumentasi_kegiatan`
  ADD PRIMARY KEY (`id_dokumentasi`),
  ADD KEY `idx_dok_kegiatan` (`id_kegiatan`,`tahap`),
  ADD KEY `fk_dok_user` (`id_user_upload`);

--
-- Indexes for table `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD UNIQUE KEY `uq_dokumen` (`id_pendaftaran`,`jenis`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `uq_guru_user` (`id_user`),
  ADD UNIQUE KEY `uq_guru_nuptk` (`nuptk`),
  ADD UNIQUE KEY `uq_guru_nik` (`nik`),
  ADD UNIQUE KEY `uq_guru_kepsek` (`kepsek_unik`);

--
-- Indexes for table `guru_kelas`
--
ALTER TABLE `guru_kelas`
  ADD PRIMARY KEY (`id_kelas`,`id_guru`),
  ADD KEY `fk_gk_guru` (`id_guru`);

--
-- Indexes for table `indikator_iqro`
--
ALTER TABLE `indikator_iqro`
  ADD PRIMARY KEY (`id_indikator`),
  ADD UNIQUE KEY `uq_indikator_urutan` (`kelompok`,`urutan`),
  ADD UNIQUE KEY `uq_indikator_nama` (`kelompok`,`kategori`,`nama`);

--
-- Indexes for table `jenis_biaya`
--
ALTER TABLE `jenis_biaya`
  ADD PRIMARY KEY (`id_jenis_biaya`),
  ADD UNIQUE KEY `uq_jenis_biaya` (`nama`);

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`),
  ADD KEY `idx_kegiatan_tanggal` (`tanggal`),
  ADD KEY `fk_kegiatan_user` (`id_user_pembuat`);

--
-- Indexes for table `kegiatan_kelas`
--
ALTER TABLE `kegiatan_kelas`
  ADD PRIMARY KEY (`id_kegiatan`,`id_kelas`),
  ADD KEY `fk_kk_kelas` (`id_kelas`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `uq_kelas` (`id_tahun_ajaran`,`kelompok`,`nama_kelas`);

--
-- Indexes for table `keluarga`
--
ALTER TABLE `keluarga`
  ADD PRIMARY KEY (`id_keluarga`),
  ADD UNIQUE KEY `uq_keluarga_user` (`id_user`),
  ADD UNIQUE KEY `uq_keluarga_kk` (`no_kk`);

--
-- Indexes for table `kontak_keluarga`
--
ALTER TABLE `kontak_keluarga`
  ADD PRIMARY KEY (`id_kontak`),
  ADD UNIQUE KEY `uq_kontak_nomor` (`id_keluarga`,`nomor`),
  ADD UNIQUE KEY `uq_kontak_urutan` (`id_keluarga`,`urutan`);

--
-- Indexes for table `nilai_iqro`
--
ALTER TABLE `nilai_iqro`
  ADD PRIMARY KEY (`id_nilai`),
  ADD UNIQUE KEY `uq_nilai_iqro` (`id_rombel`,`id_indikator`,`semester`),
  ADD KEY `fk_niqro_indikator` (`id_indikator`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `idx_bayar_tanggal` (`tanggal_bayar`),
  ADD KEY `fk_bayar_pencatat` (`id_user_pencatat`),
  ADD KEY `fk_bayar_verifikator` (`id_user_verifikator`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id_pendaftaran`),
  ADD UNIQUE KEY `uq_pendaftaran` (`id_siswa`,`id_tahun_ajaran`),
  ADD KEY `idx_pendaftaran_ta` (`id_tahun_ajaran`,`kelompok`,`status`),
  ADD KEY `fk_pendaftaran_verif` (`id_user_verifikator`);

--
-- Indexes for table `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD PRIMARY KEY (`id_pengumpulan`),
  ADD UNIQUE KEY `uq_pengumpulan` (`id_tugas`,`id_rombel`),
  ADD KEY `fk_pengumpulan_rombel` (`id_rombel`),
  ADD KEY `fk_pengumpulan_guru` (`id_guru_penilai`);

--
-- Indexes for table `portofolio`
--
ALTER TABLE `portofolio`
  ADD PRIMARY KEY (`id_portofolio`),
  ADD KEY `idx_portofolio` (`id_rombel`,`tanggal`),
  ADD KEY `fk_porto_guru` (`id_guru`);

--
-- Indexes for table `rapor`
--
ALTER TABLE `rapor`
  ADD PRIMARY KEY (`id_rapor`),
  ADD UNIQUE KEY `uq_rapor` (`id_rombel`,`semester`),
  ADD KEY `fk_rapor_kelas` (`id_guru_kelas`),
  ADD KEY `fk_rapor_iqro` (`id_guru_iqro`);

--
-- Indexes for table `rencana_pembelajaran`
--
ALTER TABLE `rencana_pembelajaran`
  ADD PRIMARY KEY (`id_rencana`),
  ADD KEY `idx_rencana` (`id_kelas`,`tanggal`),
  ADD KEY `fk_rencana_guru` (`id_guru`);

--
-- Indexes for table `rombel_siswa`
--
ALTER TABLE `rombel_siswa`
  ADD PRIMARY KEY (`id_rombel`),
  ADD UNIQUE KEY `uq_rombel` (`id_siswa`,`id_kelas`),
  ADD KEY `idx_rombel_kelas_status` (`id_kelas`,`status`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `uq_siswa_induk` (`no_induk`),
  ADD UNIQUE KEY `uq_siswa_nik` (`nik`),
  ADD UNIQUE KEY `uq_siswa_nisn` (`nisn`),
  ADD KEY `idx_siswa_nama` (`nama_lengkap`),
  ADD KEY `idx_siswa_keluarga` (`id_keluarga`);

--
-- Indexes for table `tagihan`
--
ALTER TABLE `tagihan`
  ADD PRIMARY KEY (`id_tagihan`),
  ADD UNIQUE KEY `uq_tagihan` (`id_rombel`,`kunci`),
  ADD KEY `idx_tagihan_tempo` (`jatuh_tempo`),
  ADD KEY `fk_tagihan_jenis` (`id_jenis_biaya`),
  ADD KEY `fk_tagihan_kegiatan` (`id_kegiatan`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id_tahun_ajaran`),
  ADD UNIQUE KEY `uq_ta_nama` (`nama`),
  ADD UNIQUE KEY `uq_ta_aktif` (`aktif_unik`);

--
-- Indexes for table `tarif`
--
ALTER TABLE `tarif`
  ADD PRIMARY KEY (`id_tarif`),
  ADD UNIQUE KEY `uq_tarif` (`id_tahun_ajaran`,`kelompok`,`id_jenis_biaya`),
  ADD KEY `fk_tarif_jenis` (`id_jenis_biaya`);

--
-- Indexes for table `transaksi_tabungan`
--
ALTER TABLE `transaksi_tabungan`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `idx_ttab` (`id_siswa`,`tanggal`),
  ADD KEY `fk_ttab_user` (`id_user_pencatat`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `idx_tugas` (`id_kelas`,`tenggat`),
  ADD KEY `fk_tugas_guru` (`id_guru`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `wali`
--
ALTER TABLE `wali`
  ADD PRIMARY KEY (`id_wali`),
  ADD UNIQUE KEY `uq_wali_hubungan` (`id_keluarga`,`hubungan`),
  ADD KEY `idx_wali_nama` (`nama_lengkap`);

