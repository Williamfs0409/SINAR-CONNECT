<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OBJECTS = [
        [
            'type' => 'VIEW',
            'name' => 'v_guru_lengkap',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_guru_lengkap` AS select `g`.`id_guru` AS `id_guru`,`g`.`id_user` AS `id_user`,`g`.`nuptk` AS `nuptk`,`g`.`nik` AS `nik`,trim(concat_ws(' ',`g`.`gelar_depan`,concat_ws(', ',`g`.`nama_lengkap`,`g`.`gelar_belakang`))) AS `nama_dengan_gelar`,`g`.`nama_lengkap` AS `nama_lengkap`,`g`.`jenis_kelamin` AS `jenis_kelamin`,`g`.`tempat_lahir` AS `tempat_lahir`,`g`.`tanggal_lahir` AS `tanggal_lahir`,`g`.`alamat` AS `alamat`,`g`.`no_hp` AS `no_hp`,`u`.`email` AS `email`,`g`.`pendidikan` AS `pendidikan`,`g`.`jabatan` AS `jabatan`,`g`.`status_kepegawaian` AS `status_kepegawaian`,`g`.`tanggal_mulai` AS `tanggal_mulai`,timestampdiff(YEAR,`g`.`tanggal_mulai`,curdate()) AS `masa_kerja_tahun`,`g`.`status` AS `status`,`g`.`foto` AS `foto`,(select group_concat(concat(`kl`.`nama_kelas`,' (',`gk`.`peran`,')') order by `kl`.`nama_kelas` ASC separator ', ') from ((`guru_kelas` `gk` join `kelas` `kl` on((`kl`.`id_kelas` = `gk`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`gk`.`id_guru` = `g`.`id_guru`) and (`ta`.`is_aktif` = 1))) AS `kelas_diampu` from (`guru` `g` left join `users` `u` on((`u`.`id_user` = `g`.`id_user`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_kandidat_duplikat_siswa',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_kandidat_duplikat_siswa` AS select `siswa`.`nama_lengkap` AS `nama_lengkap`,`siswa`.`tanggal_lahir` AS `tanggal_lahir`,count(0) AS `jumlah`,group_concat(`siswa`.`id_siswa` order by `siswa`.`id_siswa` ASC separator ',') AS `daftar_id_siswa` from `siswa` group by `siswa`.`nama_lengkap`,`siswa`.`tanggal_lahir` having (count(0) > 1) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_keluarga_lengkap',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_keluarga_lengkap` AS select `k`.`id_keluarga` AS `id_keluarga`,`k`.`id_user` AS `id_user`,`k`.`no_kk` AS `no_kk`,`k`.`alamat` AS `alamat`,trim(concat_ws(' ',`ay`.`gelar_depan`,concat_ws(', ',`ay`.`nama_lengkap`,`ay`.`gelar_belakang`))) AS `nama_ayah`,trim(concat_ws(' ',`ib`.`gelar_depan`,concat_ws(', ',`ib`.`nama_lengkap`,`ib`.`gelar_belakang`))) AS `nama_ibu`,`kk`.`nomor` AS `no_hp_utama` from (((`keluarga` `k` left join `wali` `ay` on(((`ay`.`id_keluarga` = `k`.`id_keluarga`) and (`ay`.`hubungan` = 'Ayah')))) left join `wali` `ib` on(((`ib`.`id_keluarga` = `k`.`id_keluarga`) and (`ib`.`hubungan` = 'Ibu')))) left join `kontak_keluarga` `kk` on(((`kk`.`id_keluarga` = `k`.`id_keluarga`) and (`kk`.`urutan` = 1)))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_kesiapan_data',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_kesiapan_data` AS select `x`.`komponen` AS `komponen`,`x`.`terisi` AS `terisi`,`x`.`dibutuhkan` AS `dibutuhkan`,if((`x`.`terisi` >= `x`.`dibutuhkan`),'Lengkap','Belum lengkap') AS `status`,`x`.`keterangan` AS `keterangan` from (select 'Tarif SPP tahun ajaran aktif' AS `komponen`,(select count(0) from ((`tarif` `tf` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `tf`.`id_tahun_ajaran`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `tf`.`id_jenis_biaya`))) where ((`ta`.`is_aktif` = 1) and (`jb`.`kategori` = 'Bulanan'))) AS `terisi`,(select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1)) AS `dibutuhkan`,'Nominal SPP tiap kelompok' AS `keterangan` union all select 'Tarif uang pendaftaran tahun ajaran aktif' AS `Tarif uang pendaftaran tahun ajaran aktif`,(select count(0) from ((`tarif` `tf` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `tf`.`id_tahun_ajaran`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `tf`.`id_jenis_biaya`))) where ((`ta`.`is_aktif` = 1) and (`jb`.`kategori` = 'Pendaftaran'))) AS `(select count(0) from ((``tarif`` ``tf`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``tf``.``id_tahun_ajaran``))) join ``jenis_biaya`` ``jb`` on((``jb``.``id_jenis_biaya`` = ``tf``.``id_jenis_biaya``))) where ((``ta``.``is_aktif`` = 1) and (``jb``.``kategori`` = 'Pendafta`,((select count(0) from `jenis_biaya` where (`jenis_biaya`.`kategori` = 'Pendaftaran')) * (select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1))) AS `((select count(0) from ``jenis_biaya`` where (``jenis_biaya``.``kategori`` = 'Pendaftaran')) * (select count(distinct ``kl``.``kelompok``) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where (``ta``.``is_aktif`` = 1)`,'Administrasi, pembangunan, baju, buku dan perlengkapan, outbond' AS `Administrasi, pembangunan, baju, buku dan perlengkapan, outbond` union all select 'Indikator rapor Iqro per kelompok' AS `Indikator rapor Iqro per kelompok`,(select count(distinct `indikator_iqro`.`kelompok`) from `indikator_iqro`) AS `(select count(distinct ``indikator_iqro``.``kelompok``) from ``indikator_iqro``)`,(select count(distinct `kl`.`kelompok`) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where (`ta`.`is_aktif` = 1)) AS `(select count(distinct ``kl``.``kelompok``) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where (``ta``.``is_aktif`` = 1))`,'KB, TK A, TK B' AS `KB, TK A, TK B` union all select 'Akun administrator TK' AS `Akun administrator TK`,(select count(0) from `users` where (`users`.`role` = 'admin')) AS `(select count(0) from ``users`` where (``users``.``role`` = 'admin'))`,1 AS `1`,'Staf pengelola data dan keuangan' AS `Staf pengelola data dan keuangan` union all select 'Akun kepala sekolah' AS `Akun kepala sekolah`,(select count(0) from `users` where (`users`.`role` = 'kepala_sekolah')) AS `(select count(0) from ``users`` where (``users``.``role`` = 'kepala_sekolah'))`,1 AS `1`,'Dibuat dari data guru' AS `Dibuat dari data guru` union all select 'Guru aktif yang punya akun' AS `Guru aktif yang punya akun`,(select count(0) from `guru` where ((`guru`.`status` = 'Aktif') and (`guru`.`id_user` is not null))) AS `(select count(0) from ``guru`` where ((``guru``.``status`` = 'Aktif') and (``guru``.``id_user`` is not null)))`,(select count(0) from `guru` where (`guru`.`status` = 'Aktif')) AS `(select count(0) from ``guru`` where (``guru``.``status`` = 'Aktif'))`,'Akun login guru' AS `Akun login guru` union all select 'Kelas aktif yang punya wali kelas' AS `Kelas aktif yang punya wali kelas`,(select count(0) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan') and exists(select 1 from `guru_kelas` `gk` where ((`gk`.`id_kelas` = `kl`.`id_kelas`) and (`gk`.`peran` = 'Wali Kelas'))))) AS `(select count(0) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where ((``ta``.``is_aktif`` = 1) and (``kl``.``nama_kelas`` <> 'Belum Ditentukan') and exists(select 1 from ``guru_kelas`` ``gk`` where ((``gk``.``id_kela`,(select count(0) from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan'))) AS `(select count(0) from (``kelas`` ``kl`` join ``tahun_ajaran`` ``ta`` on((``ta``.``id_tahun_ajaran`` = ``kl``.``id_tahun_ajaran``))) where ((``ta``.``is_aktif`` = 1) and (``kl``.``nama_kelas`` <> 'Belum Ditentukan')))`,'Penempatan guru ke kelas' AS `Penempatan guru ke kelas` union all select 'Siswa aktif sudah punya kelas definitif' AS `Siswa aktif sudah punya kelas definitif`,(select count(0) from (`rombel_siswa` `r` join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) where ((`r`.`status` = 'Aktif') and (`kl`.`nama_kelas` <> 'Belum Ditentukan'))) AS `(select count(0) from (``rombel_siswa`` ``r`` join ``kelas`` ``kl`` on((``kl``.``id_kelas`` = ``r``.``id_kelas``))) where ((``r``.``status`` = 'Aktif') and (``kl``.``nama_kelas`` <> 'Belum Ditentukan')))`,(select count(0) from `rombel_siswa` where (`rombel_siswa`.`status` = 'Aktif')) AS `(select count(0) from ``rombel_siswa`` where (``rombel_siswa``.``status`` = 'Aktif'))`,'Siswa yang masih di kelas Belum Ditentukan' AS `Siswa yang masih di kelas Belum Ditentukan` union all select 'Keluarga siswa aktif punya nomor HP' AS `Keluarga siswa aktif punya nomor HP`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) where exists(select 1 from `kontak_keluarga` `c` where (`c`.`id_keluarga` = `s`.`id_keluarga`))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))) where exists(select 1 from ``kontak_keluarga`` ``c`` where (``c``.``id_keluarga`` = ``s``.``id_keluarga``)))`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif'))))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))))`,'Kontak orang tua' AS `Kontak orang tua` union all select 'Keluarga siswa aktif punya akun login' AS `Keluarga siswa aktif punya akun login`,(select count(distinct `s`.`id_keluarga`) from ((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `keluarga` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) where (`f`.`id_user` is not null)) AS `(select count(distinct ``s``.``id_keluarga``) from ((``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))) join ``keluarga`` ``f`` on((``f``.``id_keluarga`` = ``s``.``id_keluarga``))) where (``f``.``id_user`` is not null))`,(select count(distinct `s`.`id_keluarga`) from (`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif'))))) AS `(select count(distinct ``s``.``id_keluarga``) from (``siswa`` ``s`` join ``rombel_siswa`` ``r`` on(((``r``.``id_siswa`` = ``s``.``id_siswa``) and (``r``.``status`` = 'Aktif')))))`,'Akun orang tua untuk portal' AS `Akun orang tua untuk portal`) `x` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_kualitas_data',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_kualitas_data` AS select 'siswa' AS `entitas`,`s`.`id_siswa` AS `id_record`,`s`.`nama_lengkap` AS `nama`,'Tanggal lahir kosong' AS `masalah` from `siswa` `s` where (`s`.`tanggal_lahir` is null) union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'NIK belum diisi' AS `NIK belum diisi` from `siswa` `s` where (`s`.`nik` is null) union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'Belum punya kelas' AS `Belum punya kelas` from `siswa` `s` where exists(select 1 from `rombel_siswa` `r` where (`r`.`id_siswa` = `s`.`id_siswa`)) is false union all select 'siswa' AS `siswa`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,'Siswa aktif belum ditempatkan di kelas definitif' AS `Siswa aktif belum ditempatkan di kelas definitif` from ((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) where (`kl`.`nama_kelas` = 'Belum Ditentukan') union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Ayah/ibu belum terdata' AS `Ayah/ibu belum terdata` from `keluarga` `k` where exists(select 1 from `wali` `w` where (`w`.`id_keluarga` = `k`.`id_keluarga`)) is false union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Nomor HP belum ada' AS `Nomor HP belum ada` from `keluarga` `k` where exists(select 1 from `kontak_keluarga` `c` where (`c`.`id_keluarga` = `k`.`id_keluarga`)) is false union all select 'keluarga' AS `keluarga`,`k`.`id_keluarga` AS `id_keluarga`,NULL AS `NULL`,'Alamat belum ada' AS `Alamat belum ada` from `keluarga` `k` where (`k`.`alamat` is null) union all select 'kelas' AS `kelas`,`kl`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,'Kelas tahun ajaran aktif belum punya wali kelas' AS `Kelas tahun ajaran aktif belum punya wali kelas` from (`kelas` `kl` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) where ((`ta`.`is_aktif` = 1) and (`kl`.`nama_kelas` <> 'Belum Ditentukan') and exists(select 1 from `guru_kelas` `gk` where ((`gk`.`id_kelas` = `kl`.`id_kelas`) and (`gk`.`peran` = 'Wali Kelas'))) is false) union all select 'guru' AS `guru`,`g`.`id_guru` AS `id_guru`,`g`.`nama_lengkap` AS `nama_lengkap`,'NUPTK belum diisi' AS `NUPTK belum diisi` from `guru` `g` where ((`g`.`nuptk` is null) and (`g`.`status` = 'Aktif')) union all select 'guru' AS `guru`,`g`.`id_guru` AS `id_guru`,`g`.`nama_lengkap` AS `nama_lengkap`,'Belum punya akun login' AS `Belum punya akun login` from `guru` `g` where ((`g`.`id_user` is null) and (`g`.`status` = 'Aktif')) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_laporan_kegiatan',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_laporan_kegiatan` AS select `k`.`id_kegiatan` AS `id_kegiatan`,`k`.`nama_kegiatan` AS `nama_kegiatan`,`k`.`jenis` AS `jenis`,`k`.`tanggal` AS `tanggal`,`k`.`lokasi` AS `lokasi`,`k`.`biaya` AS `biaya`,`k`.`seluruh_sekolah` AS `seluruh_sekolah`,(select group_concat(`kl`.`nama_kelas` order by `kl`.`nama_kelas` ASC separator ', ') from (`kegiatan_kelas` `kk` join `kelas` `kl` on((`kl`.`id_kelas` = `kk`.`id_kelas`))) where (`kk`.`id_kegiatan` = `k`.`id_kegiatan`)) AS `kelas_terlibat`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Sebelum'))) AS `laporan_sebelum`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Selama'))) AS `laporan_selama`,(select count(0) from `dokumentasi_kegiatan` `d` where ((`d`.`id_kegiatan` = `k`.`id_kegiatan`) and (`d`.`tahap` = 'Sesudah'))) AS `laporan_sesudah`,(select count(0) from `tagihan` `t` where (`t`.`id_kegiatan` = `k`.`id_kegiatan`)) AS `tagihan_terbit` from `kegiatan` `k` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_pembayaran_detail',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_pembayaran_detail` AS select `p`.`id_pembayaran` AS `id_pembayaran`,`fn_no_kuitansi`(`p`.`id_pembayaran`,`p`.`tanggal_bayar`) AS `no_kuitansi`,`p`.`tanggal_bayar` AS `tanggal_bayar`,`p`.`metode` AS `metode`,`p`.`status` AS `status`,count(`a`.`id_alokasi`) AS `jumlah_tagihan`,coalesce(sum(`a`.`jumlah`),0) AS `total_bayar`,`p`.`bukti_transfer` AS `bukti_transfer`,`p`.`id_user_pencatat` AS `id_user_pencatat`,`p`.`id_user_verifikator` AS `id_user_verifikator`,`p`.`tanggal_verifikasi` AS `tanggal_verifikasi` from (`pembayaran` `p` left join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) group by `p`.`id_pembayaran`,`p`.`tanggal_bayar`,`p`.`metode`,`p`.`status`,`p`.`bukti_transfer`,`p`.`id_user_pencatat`,`p`.`id_user_verifikator`,`p`.`tanggal_verifikasi` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_pengumpulan_detail',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_pengumpulan_detail` AS select `pt`.`id_pengumpulan` AS `id_pengumpulan`,`pt`.`id_tugas` AS `id_tugas`,`t`.`judul` AS `judul`,`t`.`id_kelas` AS `id_kelas`,`r`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`pt`.`dikumpulkan_pada` AS `dikumpulkan_pada`,`t`.`tenggat` AS `tenggat`,(case when (`pt`.`nilai` is not null) then 'Dinilai' when (cast(`pt`.`dikumpulkan_pada` as date) > `t`.`tenggat`) then 'Terlambat' else 'Dikumpulkan' end) AS `status`,`pt`.`nilai` AS `nilai`,`pt`.`umpan_balik` AS `umpan_balik` from (((`pengumpulan_tugas` `pt` join `tugas` `t` on((`t`.`id_tugas` = `pt`.`id_tugas`))) join `rombel_siswa` `r` on((`r`.`id_rombel` = `pt`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_rapor_iqro',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_rapor_iqro` AS select `r`.`id_rombel` AS `id_rombel`,`r`.`id_siswa` AS `id_siswa`,`i`.`id_indikator` AS `id_indikator`,`i`.`kategori` AS `kategori`,`i`.`nama` AS `indikator`,`i`.`urutan` AS `urutan`,max((case when (`n`.`semester` = 'Ganjil') then `n`.`skala` end)) AS `semester_1`,max((case when (`n`.`semester` = 'Genap') then `n`.`skala` end)) AS `semester_2` from (((`rombel_siswa` `r` join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `indikator_iqro` `i` on((`i`.`kelompok` = `kl`.`kelompok`))) left join `nilai_iqro` `n` on(((`n`.`id_rombel` = `r`.`id_rombel`) and (`n`.`id_indikator` = `i`.`id_indikator`)))) group by `r`.`id_rombel`,`r`.`id_siswa`,`i`.`id_indikator`,`i`.`kategori`,`i`.`nama`,`i`.`urutan` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_rapor_lengkap',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_rapor_lengkap` AS select `rp`.`id_rapor` AS `id_rapor`,`rp`.`id_rombel` AS `id_rombel`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`kl`.`nama_kelas` AS `nama_kelas`,`kl`.`kelompok` AS `kelompok`,`ta`.`nama` AS `tahun_ajaran`,`rp`.`semester` AS `semester`,`rp`.`status` AS `status`,`rp`.`tanggal_rapor` AS `tanggal_rapor`,`rp`.`tinggi_badan` AS `tinggi_badan`,`rp`.`berat_badan` AS `berat_badan`,(select coalesce(sum((`a`.`status` = 'Sakit')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `sakit`,(select coalesce(sum((`a`.`status` = 'Izin')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `izin`,(select coalesce(sum((`a`.`status` = 'Alfa')),0) from `absensi` `a` where ((`a`.`id_rombel` = `rp`.`id_rombel`) and (`a`.`tanggal` between `fn_awal_semester`(`ta`.`tahun_mulai`,`rp`.`semester`) and `fn_akhir_semester`(`ta`.`tahun_mulai`,`rp`.`semester`)))) AS `tanpa_keterangan`,`rp`.`narasi_agama_budi_pekerti` AS `narasi_agama_budi_pekerti`,`rp`.`narasi_jati_diri` AS `narasi_jati_diri`,`rp`.`narasi_literasi_steam` AS `narasi_literasi_steam`,`rp`.`narasi_p5` AS `narasi_p5`,`rp`.`refleksi_orang_tua` AS `refleksi_orang_tua`,trim(concat_ws(' ',`gk`.`gelar_depan`,concat_ws(', ',`gk`.`nama_lengkap`,`gk`.`gelar_belakang`))) AS `guru_kelas`,trim(concat_ws(' ',`gi`.`gelar_depan`,concat_ws(', ',`gi`.`nama_lengkap`,`gi`.`gelar_belakang`))) AS `guru_iqro`,(select trim(concat_ws(' ',`g`.`gelar_depan`,concat_ws(', ',`g`.`nama_lengkap`,`g`.`gelar_belakang`))) from `guru` `g` where (`g`.`kepsek_unik` = 1)) AS `kepala_sekolah` from ((((((`rapor` `rp` join `rombel_siswa` `r` on((`r`.`id_rombel` = `rp`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) left join `guru` `gk` on((`gk`.`id_guru` = `rp`.`id_guru_kelas`))) left join `guru` `gi` on((`gi`.`id_guru` = `rp`.`id_guru_iqro`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_rekap_kehadiran_bulanan',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_rekap_kehadiran_bulanan` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`r`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,year(`a`.`tanggal`) AS `tahun`,month(`a`.`tanggal`) AS `bulan`,sum((`a`.`status` = 'Hadir')) AS `hadir`,sum((`a`.`status` = 'Izin')) AS `izin`,sum((`a`.`status` = 'Sakit')) AS `sakit`,sum((`a`.`status` = 'Alfa')) AS `alfa`,count(0) AS `total_hari`,round(((100 * sum((`a`.`status` = 'Hadir'))) / count(0)),2) AS `persen_hadir` from (((`absensi` `a` join `rombel_siswa` `r` on((`r`.`id_rombel` = `a`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) group by `s`.`id_siswa`,`s`.`nama_lengkap`,`r`.`id_kelas`,`kl`.`nama_kelas`,year(`a`.`tanggal`),month(`a`.`tanggal`) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_rekap_pembayaran_bulanan',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_rekap_pembayaran_bulanan` AS select date_format(`p`.`tanggal_bayar`,'%Y-%m') AS `bulan`,`jb`.`nama` AS `jenis_biaya`,`p`.`metode` AS `metode`,count(distinct `p`.`id_pembayaran`) AS `jumlah_kuitansi`,sum(`a`.`jumlah`) AS `total_diterima` from (((`pembayaran` `p` join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) join `tagihan` `t` on((`t`.`id_tagihan` = `a`.`id_tagihan`))) join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `t`.`id_jenis_biaya`))) where (`p`.`status` = 'Terverifikasi') group by date_format(`p`.`tanggal_bayar`,'%Y-%m'),`jb`.`nama`,`p`.`metode` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_rekap_pendaftar',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_rekap_pendaftar` AS select `ta`.`nama` AS `tahun_ajaran`,`p`.`kelompok` AS `kelompok`,count(0) AS `total_pendaftar`,sum((`p`.`status` = 'Diajukan')) AS `diajukan`,sum((`p`.`status` = 'Diverifikasi')) AS `diverifikasi`,sum((`p`.`status` = 'Diterima')) AS `diterima`,sum((`p`.`status` = 'Ditolak')) AS `ditolak` from (`pendaftaran` `p` join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `p`.`id_tahun_ajaran`))) group by `ta`.`nama`,`p`.`kelompok` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_saldo_tabungan',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_saldo_tabungan` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,sum(if((`t`.`jenis` = 'Setor'),`t`.`jumlah`,-(`t`.`jumlah`))) AS `saldo`,count(0) AS `jumlah_transaksi`,max(`t`.`tanggal`) AS `transaksi_terakhir` from (`siswa` `s` join `transaksi_tabungan` `t` on((`t`.`id_siswa` = `s`.`id_siswa`))) group by `s`.`id_siswa`,`s`.`nama_lengkap` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_siswa_guru',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_siswa_guru` AS select `s`.`id_siswa` AS `id_siswa`,`r`.`id_rombel` AS `id_rombel`,`kl`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok`,`r`.`status` AS `status_rombel`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`nama_panggilan` AS `nama_panggilan`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`tanggal_lahir` AS `tanggal_lahir`,`fn_hitung_usia`(`s`.`tanggal_lahir`) AS `usia` from (((`siswa` `s` join `rombel_siswa` `r` on((`r`.`id_siswa` = `s`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_siswa_terlindungi',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_siswa_terlindungi` AS select `s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`tanggal_lahir` AS `tanggal_lahir`,if((`s`.`nik` is null),NULL,concat(left(`s`.`nik`,4),repeat('*',8),right(`s`.`nik`,4))) AS `nik_samar`,if((`s`.`nisn` is null),NULL,concat(repeat('*',7),right(`s`.`nisn`,3))) AS `nisn_samar`,`kl`.`nama_kelas` AS `nama_kelas`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok` from (((`siswa` `s` join `rombel_siswa` `r` on(((`r`.`id_siswa` = `s`.`id_siswa`) and (`r`.`status` = 'Aktif')))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_status_pengumpulan_tugas',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_status_pengumpulan_tugas` AS select `t`.`id_tugas` AS `id_tugas`,`t`.`judul` AS `judul`,`t`.`id_kelas` AS `id_kelas`,`kl`.`nama_kelas` AS `nama_kelas`,`t`.`tenggat` AS `tenggat`,(select count(0) from `rombel_siswa` `r` where ((`r`.`id_kelas` = `t`.`id_kelas`) and (`r`.`status` = 'Aktif'))) AS `total_siswa`,count(`pt`.`id_pengumpulan`) AS `sudah_mengumpulkan`,coalesce(sum((cast(`pt`.`dikumpulkan_pada` as date) > `t`.`tenggat`)),0) AS `terlambat`,coalesce(sum((`pt`.`nilai` is not null)),0) AS `sudah_dinilai` from ((`tugas` `t` join `kelas` `kl` on((`kl`.`id_kelas` = `t`.`id_kelas`))) left join `pengumpulan_tugas` `pt` on((`pt`.`id_tugas` = `t`.`id_tugas`))) group by `t`.`id_tugas`,`t`.`judul`,`t`.`id_kelas`,`kl`.`nama_kelas`,`t`.`tenggat` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_tagihan_detail',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_tagihan_detail` AS select `t`.`id_tagihan` AS `id_tagihan`,`r`.`id_rombel` AS `id_rombel`,`s`.`id_siswa` AS `id_siswa`,`s`.`id_keluarga` AS `id_keluarga`,`s`.`nama_lengkap` AS `nama_lengkap`,`kl`.`nama_kelas` AS `nama_kelas`,`jb`.`nama` AS `jenis_biaya`,`jb`.`kategori` AS `kategori`,`t`.`periode` AS `periode`,`kg`.`nama_kegiatan` AS `nama_kegiatan`,`t`.`total_tagihan` AS `total_tagihan`,coalesce(`d`.`dibayar`,0) AS `dibayar`,(`t`.`total_tagihan` - coalesce(`d`.`dibayar`,0)) AS `sisa`,`t`.`jatuh_tempo` AS `jatuh_tempo`,(case when (coalesce(`d`.`dibayar`,0) >= `t`.`total_tagihan`) then 'Lunas' when (coalesce(`d`.`dibayar`,0) > 0) then 'Sebagian' else 'Belum Bayar' end) AS `status_bayar`,greatest((to_days(curdate()) - to_days(`t`.`jatuh_tempo`)),0) AS `hari_terlambat` from ((((((`tagihan` `t` join `jenis_biaya` `jb` on((`jb`.`id_jenis_biaya` = `t`.`id_jenis_biaya`))) join `rombel_siswa` `r` on((`r`.`id_rombel` = `t`.`id_rombel`))) join `siswa` `s` on((`s`.`id_siswa` = `r`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) left join `kegiatan` `kg` on((`kg`.`id_kegiatan` = `t`.`id_kegiatan`))) left join (select `a`.`id_tagihan` AS `id_tagihan`,sum(`a`.`jumlah`) AS `dibayar` from (`alokasi_pembayaran` `a` join `pembayaran` `p` on((`p`.`id_pembayaran` = `a`.`id_pembayaran`))) where (`p`.`status` = 'Terverifikasi') group by `a`.`id_tagihan`) `d` on((`d`.`id_tagihan` = `t`.`id_tagihan`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_dashboard_kepala_sekolah',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_dashboard_kepala_sekolah` AS select (select count(distinct `rombel_siswa`.`id_siswa`) from `rombel_siswa` where (`rombel_siswa`.`status` = 'Aktif')) AS `siswa_aktif`,(select count(0) from `pendaftaran` where (`pendaftaran`.`status` in ('Diajukan','Diverifikasi'))) AS `pendaftar_menunggu`,(select coalesce(sum(`v_tagihan_detail`.`sisa`),0) from `v_tagihan_detail` where ((`v_tagihan_detail`.`sisa` > 0) and (`v_tagihan_detail`.`jatuh_tempo` < curdate()))) AS `total_tunggakan`,(select coalesce(sum(`a`.`jumlah`),0) from (`pembayaran` `p` join `alokasi_pembayaran` `a` on((`a`.`id_pembayaran` = `p`.`id_pembayaran`))) where ((`p`.`status` = 'Terverifikasi') and (`p`.`tanggal_bayar` >= date_format(curdate(),'%Y-%m-01')) and (`p`.`tanggal_bayar` < (date_format(curdate(),'%Y-%m-01') + interval 1 month)))) AS `penerimaan_bulan_ini`,(select round(((100 * sum((`absensi`.`status` = 'Hadir'))) / nullif(count(0),0)),2) from `absensi` where (`absensi`.`tanggal` = curdate())) AS `persen_hadir_hari_ini`,(select coalesce(sum(if((`transaksi_tabungan`.`jenis` = 'Setor'),`transaksi_tabungan`.`jumlah`,-(`transaksi_tabungan`.`jumlah`))),0) from `transaksi_tabungan`) AS `total_saldo_tabungan` */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_pendaftaran_detail',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_pendaftaran_detail` AS select `p`.`id_pendaftaran` AS `id_pendaftaran`,`fn_no_pendaftaran`(`p`.`id_pendaftaran`,`ta`.`tahun_mulai`) AS `no_pendaftaran`,`p`.`status` AS `status`,`ta`.`nama` AS `tahun_ajaran`,`p`.`kelompok` AS `kelompok_tujuan`,`s`.`id_siswa` AS `id_siswa`,`s`.`nama_lengkap` AS `nama_lengkap`,`s`.`tanggal_lahir` AS `tanggal_lahir`,`f`.`nama_ayah` AS `nama_ayah`,`f`.`nama_ibu` AS `nama_ibu`,`f`.`no_hp_utama` AS `no_hp_utama`,(select count(0) from `dokumen_pendaftaran` `d` where (`d`.`id_pendaftaran` = `p`.`id_pendaftaran`)) AS `dokumen_diunggah`,(select count(0) from `dokumen_pendaftaran` `d` where ((`d`.`id_pendaftaran` = `p`.`id_pendaftaran`) and (`d`.`status_verifikasi` = 'Valid'))) AS `dokumen_valid`,`p`.`catatan_verifikasi` AS `catatan_verifikasi`,`p`.`tanggal_verifikasi` AS `tanggal_verifikasi`,`p`.`created_at` AS `tanggal_daftar` from (((`pendaftaran` `p` join `siswa` `s` on((`s`.`id_siswa` = `p`.`id_siswa`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `p`.`id_tahun_ajaran`))) left join `v_keluarga_lengkap` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_siswa_lengkap',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_siswa_lengkap` AS select `s`.`id_siswa` AS `id_siswa`,`r`.`id_rombel` AS `id_rombel`,`kl`.`id_kelas` AS `id_kelas`,`ta`.`id_tahun_ajaran` AS `id_tahun_ajaran`,`ta`.`nama` AS `tahun_ajaran`,`kl`.`kelompok` AS `kelompok`,`kl`.`nama_kelas` AS `nama_kelas`,`r`.`status` AS `status_rombel`,`s`.`no_induk` AS `no_induk`,`s`.`nik` AS `nik`,`s`.`nisn` AS `nisn`,`s`.`nama_lengkap` AS `nama_siswa`,`s`.`nama_panggilan` AS `nama_panggilan`,`s`.`tempat_lahir` AS `tempat_lahir_siswa`,`s`.`tanggal_lahir` AS `tanggal_lahir_siswa`,`s`.`jenis_kelamin` AS `jenis_kelamin`,`s`.`agama` AS `agama`,`s`.`anak_ke` AS `anak_ke`,`s`.`kewarganegaraan` AS `kewarganegaraan`,coalesce(`s`.`alamat_domisili`,`f`.`alamat`) AS `alamat_siswa`,`f`.`id_keluarga` AS `id_keluarga`,`f`.`nama_ayah` AS `nama_ayah`,`f`.`nama_ibu` AS `nama_ibu`,`f`.`no_hp_utama` AS `no_hp` from ((((`siswa` `s` join `rombel_siswa` `r` on((`r`.`id_siswa` = `s`.`id_siswa`))) join `kelas` `kl` on((`kl`.`id_kelas` = `r`.`id_kelas`))) join `tahun_ajaran` `ta` on((`ta`.`id_tahun_ajaran` = `kl`.`id_tahun_ajaran`))) left join `v_keluarga_lengkap` `f` on((`f`.`id_keluarga` = `s`.`id_keluarga`))) */
            DB_TK_SQL,
        ],
        [
            'type' => 'VIEW',
            'name' => 'v_tunggakan',
            'sql' => <<<'DB_TK_SQL'
            /*!50001 CREATE ALGORITHM=UNDEFINED */
            /*!50013 SQL SECURITY DEFINER */
            /*!50001 VIEW `v_tunggakan` AS select `v_tagihan_detail`.`id_siswa` AS `id_siswa`,`v_tagihan_detail`.`id_keluarga` AS `id_keluarga`,`v_tagihan_detail`.`nama_lengkap` AS `nama_lengkap`,`v_tagihan_detail`.`nama_kelas` AS `nama_kelas`,count(0) AS `jumlah_tagihan_menunggak`,sum(`v_tagihan_detail`.`sisa`) AS `total_tunggakan`,min(`v_tagihan_detail`.`jatuh_tempo`) AS `jatuh_tempo_tertua`,max(`v_tagihan_detail`.`hari_terlambat`) AS `keterlambatan_terlama_hari` from `v_tagihan_detail` where ((`v_tagihan_detail`.`sisa` > 0) and (`v_tagihan_detail`.`jatuh_tempo` < curdate())) group by `v_tagihan_detail`.`id_siswa`,`v_tagihan_detail`.`id_keluarga`,`v_tagihan_detail`.`nama_lengkap`,`v_tagihan_detail`.`nama_kelas` */
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
