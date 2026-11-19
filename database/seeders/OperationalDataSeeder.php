<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class OperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('siswa') || ! Schema::hasTable('tahun_ajaran')) {
            return;
        }

        $this->seedSampleFiles();
        $this->seedReferenceCompleteness();
        $this->seedAttendance();
        $this->seedLessonPlans();
        $this->seedTasksAndSubmissions();
        $this->seedIqroGrades();
        $this->seedPortfolios();
        $this->seedReports();
        $this->seedRegistrations();
        $this->seedActivitiesAndDocumentation();
        $this->seedBillingAndPayments();
        $this->seedSavings();
        $this->seedMessages();
        $this->seedAuditLog();
    }

    private function seedSampleFiles(): void
    {
        $disk = Storage::disk('local');

        $image = imagecreatetruecolor(320, 200);
        $bg = imagecolorallocate($image, 232, 245, 233);
        $accent = imagecolorallocate($image, 46, 125, 50);
        imagefilledrectangle($image, 0, 0, 320, 200, $bg);
        imagefilledrectangle($image, 20, 20, 300, 180, $accent);
        ob_start();
        imagepng($image);
        $pngBytes = (string) ob_get_clean();
        imagedestroy($image);

        foreach ([
            'kegiatan/sample-kegiatan.png',
            'portofolio/sample-portofolio.png',
            'bukti-pembayaran/sample-bukti.png',
            'pengumpulan-tugas/hasil-karya.png',
        ] as $path) {
            if (! $disk->exists($path)) {
                $disk->put($path, $pngBytes);
            }
        }

        $pdfBytes = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n164\n%%EOF\n";
        foreach ([
            'tugas/lembar-kerja.pdf',
            'pendaftaran/berkas-pendaftaran.pdf',
        ] as $path) {
            if (! $disk->exists($path)) {
                $disk->put($path, $pdfBytes);
            }
        }
    }

    private function seedReferenceCompleteness(): void
    {
        $activeYearId = DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran') ?? 3;

        $registrationTariffs = [
            2 => ['KB' => 150000, 'TK A' => 150000, 'TK B' => 150000],
            3 => ['KB' => 1200000, 'TK A' => 1500000, 'TK B' => 1500000],
            4 => ['KB' => 450000, 'TK A' => 550000, 'TK B' => 550000],
            5 => ['KB' => 350000, 'TK A' => 450000, 'TK B' => 450000],
            6 => ['KB' => 250000, 'TK A' => 300000, 'TK B' => 300000],
        ];

        foreach ($registrationTariffs as $feeId => $groups) {
            if (! DB::table('jenis_biaya')->where('id_jenis_biaya', $feeId)->exists()) {
                continue;
            }
            foreach ($groups as $kelompok => $nominal) {
                DB::table('tarif')->updateOrInsert(
                    [
                        'id_tahun_ajaran' => $activeYearId,
                        'id_jenis_biaya' => $feeId,
                        'kelompok' => $kelompok,
                    ],
                    [
                        'nominal' => $nominal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('indikator_iqro')
            && DB::table('indikator_iqro')->where('kelompok', 'TK B')->count() === 0) {
            $tkaIndicators = DB::table('indikator_iqro')
                ->where('kelompok', 'TK A')
                ->orderBy('urutan')
                ->get();

            foreach ($tkaIndicators as $ind) {
                DB::table('indikator_iqro')->insert([
                    'kelompok' => 'TK B',
                    'kategori' => $ind->kategori,
                    'nama' => $ind->nama,
                    'urutan' => $ind->urutan,
                ]);
            }
        }

        $unassignedRombels = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->where('r.status', 'Aktif')
            ->where('k.nama_kelas', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->pluck('r.id_rombel')
            ->all();

        if ($unassignedRombels !== []) {
            $targetClasses = DB::table('kelas')
                ->where('id_tahun_ajaran', $activeYearId)
                ->where('nama_kelas', '<>', 'Belum Ditentukan')
                ->orderBy('id_kelas')
                ->get(['id_kelas', 'kapasitas']);

            foreach ($unassignedRombels as $rombelId) {
                foreach ($targetClasses as $class) {
                    $currentCount = DB::table('rombel_siswa')
                        ->where('id_kelas', $class->id_kelas)
                        ->where('status', 'Aktif')
                        ->count();
                    if ($class->kapasitas === null || $currentCount < (int) $class->kapasitas) {
                        DB::table('rombel_siswa')
                            ->where('id_rombel', $rombelId)
                            ->update(['id_kelas' => $class->id_kelas, 'updated_at' => now()]);
                        break;
                    }
                }
            }
        }

        if (Schema::hasTable('diskon') && Schema::hasColumn('rombel_siswa', 'id_diskon')) {
            $discountedCount = DB::table('rombel_siswa')->whereNotNull('id_diskon')->count();
            if ($discountedCount === 0) {
                $activeRombelIds = DB::table('rombel_siswa')
                    ->where('status', 'Aktif')
                    ->orderBy('id_rombel')
                    ->limit(4)
                    ->pluck('id_rombel')
                    ->all();
                if (isset($activeRombelIds[0])) {
                    DB::table('rombel_siswa')->where('id_rombel', $activeRombelIds[0])->update(['id_diskon' => 1]);
                }
                if (isset($activeRombelIds[1])) {
                    DB::table('rombel_siswa')->where('id_rombel', $activeRombelIds[1])->update(['id_diskon' => 1]);
                }
                if (isset($activeRombelIds[2])) {
                    DB::table('rombel_siswa')->where('id_rombel', $activeRombelIds[2])->update(['id_diskon' => 2]);
                }
                if (isset($activeRombelIds[3])) {
                    DB::table('rombel_siswa')->where('id_rombel', $activeRombelIds[3])->update(['id_diskon' => 2]);
                }
            }
        }

        $familiesWithoutContact = DB::table('rombel_siswa as r')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('r.status', 'Aktif')
            ->whereNotExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('kontak_keluarga as kk')
                    ->whereColumn('kk.id_keluarga', 's.id_keluarga');
            })
            ->distinct()
            ->pluck('s.id_keluarga')
            ->all();

        foreach ($familiesWithoutContact as $index => $familyId) {
            DB::table('kontak_keluarga')->insert([
                'id_keluarga' => $familyId,
                'nomor' => '08126500'.str_pad((string) ($index + 101), 4, '0', STR_PAD_LEFT),
                'urutan' => 1,
            ]);
        }
    }

    private function seedAttendance(): void
    {
        if (! Schema::hasTable('absensi')) {
            return;
        }

        $today = now('Asia/Jakarta')->toDateString();
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 1;
        $dates = array_values(array_unique(['2026-11-11', '2026-11-12', '2026-11-13', '2026-11-16', '2026-11-17', $today]));
        $activeRombels = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('r.status', 'Aktif')
            ->where('ta.is_aktif', true)
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->get(['r.id_rombel', 'r.id_kelas']);

        $rows = [];
        foreach ($dates as $dIdx => $date) {
            if (DB::table('absensi')->whereDate('tanggal', $date)->exists()) {
                continue;
            }
            foreach ($activeRombels as $rIdx => $rombel) {
                $mod = ($dIdx + $rIdx) % 19;
                $status = match (true) {
                    $mod === 3 => 'Sakit',
                    $mod === 9 => 'Izin',
                    default => 'Hadir',
                };
                $keterangan = match ($status) {
                    'Sakit' => 'Demam ringan (konfirmasi orang tua)',
                    'Izin' => 'Acara keluarga',
                    default => 'Hadir tepat waktu',
                };

                $rows[] = [
                    'id_rombel' => $rombel->id_rombel,
                    'tanggal' => $date,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'id_user_pencatat' => $adminUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('absensi')->insert($chunk);
        }
    }

    private function seedLessonPlans(): void
    {
        if (! Schema::hasTable('rencana_pembelajaran') || DB::table('rencana_pembelajaran')->count() > 0) {
            return;
        }

        $classes = DB::table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('ta.is_aktif', true)
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('k.id_kelas')
            ->get(['k.id_kelas', 'k.nama_kelas', 'k.kelompok']);

        $themes = [
            ['jenis' => 'RPPH', 'tema' => 'Diriku dan Keluargaku', 'subtema' => 'Mengenal Anggota Tubuh & Bersyukur', 'tujuan' => 'Anak mampu menyebutkan fungsi anggota tubuh dan membiasakan doa harian.', 'kegiatan' => 'Bernyanyi anggota tubuh, menggambar telapak tangan, dan praktik mencuci tangan.', 'alat_bahan' => 'Kertas gambar, krayon warna, sabun cuci tangan'],
            ['jenis' => 'Modul Ajar', 'tema' => 'Lingkungan Sekitarku', 'subtema' => 'Kebersihan Taman Sekolah', 'tujuan' => 'Anak memiliki kepedulian menjaga kebersihan lingkungan kelas dan halaman bermain.', 'kegiatan' => 'Observasi tanaman sekolah, memilah sampah organik/anorganik, dan menyiram bunga.', 'alat_bahan' => 'Tempat sampah warna, alat penyiram tanaman, kartu gambar'],
        ];

        foreach ($classes as $idx => $class) {
            $teacherId = DB::table('guru_kelas')
                ->where('id_kelas', $class->id_kelas)
                ->value('id_guru')
                ?? DB::table('guru')->orderBy('id_guru')->value('id_guru')
                ?? 1;

            foreach ($themes as $tIdx => $theme) {
                $startDate = sprintf('2026-11-%02d', 10 + $idx + ($tIdx * 3));
                DB::table('rencana_pembelajaran')->insert([
                    'id_kelas' => $class->id_kelas,
                    'id_guru' => $teacherId,
                    'jenis' => $theme['jenis'],
                    'tanggal' => $startDate,
                    'tanggal_selesai' => $startDate,
                    'tema' => $theme['tema'],
                    'subtema' => $theme['subtema'],
                    'tujuan' => $theme['tujuan'],
                    'kegiatan' => $theme['kegiatan'],
                    'alat_bahan' => $theme['alat_bahan'],
                    'status' => 'Final',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedTasksAndSubmissions(): void
    {
        if (! Schema::hasTable('tugas') || DB::table('tugas')->count() > 0) {
            return;
        }

        $tkClasses = DB::table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('ta.is_aktif', true)
            ->whereIn('k.kelompok', ['TK A', 'TK B'])
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('k.id_kelas')
            ->get(['k.id_kelas', 'k.nama_kelas', 'k.kelompok']);

        foreach ($tkClasses as $idx => $class) {
            $teacherId = DB::table('guru_kelas')
                ->where('id_kelas', $class->id_kelas)
                ->value('id_guru')
                ?? 1;

            $taskId = DB::table('tugas')->insertGetId([
                'id_kelas' => $class->id_kelas,
                'id_guru' => $teacherId,
                'judul' => 'Kolase Bentuk Geometri & Hafalan Doa Sebelum Makan ('.$class->nama_kelas.')',
                'kategori' => 'Motorik Halus & Agama',
                'deskripsi' => 'Tempelkan potongan kertas warna pada pola gambar rumah dan kirimkan foto hasil karya anak.',
                'tanggal_diberikan' => '2026-11-10',
                'tenggat' => '2026-11-25',
                'lampiran' => 'tugas/lembar-kerja.pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rombels = DB::table('rombel_siswa')
                ->where('id_kelas', $class->id_kelas)
                ->where('status', 'Aktif')
                ->orderBy('id_rombel')
                ->limit(4)
                ->pluck('id_rombel')
                ->all();

            foreach ($rombels as $sIdx => $rombelId) {
                $isGraded = $sIdx < 3;
                DB::table('pengumpulan_tugas')->insert([
                    'id_tugas' => $taskId,
                    'id_rombel' => $rombelId,
                    'file_path' => 'pengumpulan-tugas/hasil-karya.png',
                    'catatan_ortu' => 'Ananda mengerjakan dengan sangat antusias di rumah bersama Ibu.',
                    'dikumpulkan_pada' => '2026-11-14 16:30:00',
                    'nilai' => $isGraded ? ($sIdx % 2 === 0 ? 'BSB' : 'BSH') : null,
                    'umpan_balik' => $isGraded ? 'Alhamdulillah, kerapian kolase dan koordinasi motorik halus sangat baik.' : null,
                    'id_guru_penilai' => $isGraded ? $teacherId : null,
                    'dinilai_pada' => $isGraded ? '2026-11-15 09:00:00' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedIqroGrades(): void
    {
        if (! Schema::hasTable('nilai_iqro') || DB::table('nilai_iqro')->count() > 0) {
            return;
        }

        $activeRombels = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('r.status', 'Aktif')
            ->where('ta.is_aktif', true)
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->limit(24)
            ->get(['r.id_rombel', 'k.kelompok']);

        $indicatorsByGroup = DB::table('indikator_iqro')
            ->orderBy('urutan')
            ->get()
            ->groupBy('kelompok');

        $scales = ['BSH', 'BSB', 'BSH', 'MB'];
        $rows = [];

        foreach ($activeRombels as $rIdx => $rombel) {
            $groupIndicators = ($indicatorsByGroup->get($rombel->kelompok) ?? collect())->take(6);
            foreach ($groupIndicators as $iIdx => $ind) {
                $rows[] = [
                    'id_rombel' => $rombel->id_rombel,
                    'id_indikator' => $ind->id_indikator,
                    'semester' => 'Ganjil',
                    'skala' => $scales[($rIdx + $iIdx) % count($scales)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('nilai_iqro')->insert($chunk);
        }
    }

    private function seedPortfolios(): void
    {
        if (! Schema::hasTable('portofolio') || DB::table('portofolio')->count() > 0) {
            return;
        }

        $activeRombels = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('r.status', 'Aktif')
            ->where('ta.is_aktif', true)
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->limit(15)
            ->get(['r.id_rombel', 'r.id_kelas']);

        foreach ($activeRombels as $idx => $rombel) {
            $teacherId = DB::table('guru_kelas')
                ->where('id_kelas', $rombel->id_kelas)
                ->value('id_guru')
                ?? 1;

            DB::table('portofolio')->insert([
                'id_rombel' => $rombel->id_rombel,
                'tanggal' => sprintf('2026-11-%02d', 5 + ($idx % 12)),
                'foto' => 'portofolio/sample-portofolio.png',
                'narasi' => 'Ananda menunjukkan kemandirian saat merapikan alat bermain, mampu bekerja sama dengan teman sebaya, dan antusias menceritakan hasil karyanya di depan kelas.',
                'id_guru' => $teacherId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedReports(): void
    {
        if (! Schema::hasTable('rapor') || DB::table('rapor')->count() > 0) {
            return;
        }

        $activeRombels = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('r.status', 'Aktif')
            ->where('ta.is_aktif', true)
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->limit(18)
            ->get(['r.id_rombel', 'r.id_kelas']);

        foreach ($activeRombels as $idx => $rombel) {
            $teacherId = DB::table('guru_kelas')
                ->where('id_kelas', $rombel->id_kelas)
                ->where('peran', 'Wali Kelas')
                ->value('id_guru')
                ?? DB::table('guru_kelas')->where('id_kelas', $rombel->id_kelas)->value('id_guru')
                ?? 1;
            $iqroTeacherId = DB::table('guru_kelas')
                ->where('id_kelas', $rombel->id_kelas)
                ->where('peran', 'Pendamping')
                ->value('id_guru')
                ?? $teacherId;

            DB::table('rapor')->insert([
                'id_rombel' => $rombel->id_rombel,
                'semester' => 'Ganjil',
                'tanggal_rapor' => '2026-12-19',
                'tinggi_badan' => 104.5 + ($idx % 8),
                'berat_badan' => 16.5 + ($idx % 5),
                'narasi_agama_budi_pekerti' => 'Alhamdulillah, ananda terbiasa mengucapkan salam, membaca doa sebelum dan sesudah belajar, serta mengenal huruf hijaiyah dengan lafal yang baik.',
                'narasi_jati_diri' => 'Ananda mampu mengenali emosi diri, sabar menunggu giliran saat bermain bersama teman, dan memiliki koordinasi motorik kasar serta halus yang berkembang sesuai harapan.',
                'narasi_literasi_steam' => 'Ananda menunjukkan rasa ingin tahu yang tinggi, mampu mengelompokkan benda berdasarkan warna dan bentuk, serta mengenal lambang bilangan 1-10.',
                'narasi_p5' => 'Dalam projek kebersamaan dan cinta lingkungan, ananda aktif bergotong royong membersihkan kelas dan merawat tanaman bersama teman-teman.',
                'refleksi_orang_tua' => $idx % 2 === 0
                    ? 'Terima kasih Ibu Guru atas bimbingannya. Di rumah ananda semakin rajin berdoa dan mandiri merapikan mainannya.'
                    : null,
                'id_guru_kelas' => $teacherId,
                'id_guru_iqro' => $iqroTeacherId,
                'status' => $idx < 12 ? 'Final' : 'Draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedRegistrations(): void
    {
        if (! Schema::hasTable('pendaftaran') || DB::table('pendaftaran')->count() > 0) {
            return;
        }

        $activeYearId = DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran') ?? 3;
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 1;

        $sampleStudents = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->where('k.id_tahun_ajaran', $activeYearId)
            ->where('r.status', 'Aktif')
            ->orderBy('r.id_rombel')
            ->limit(5)
            ->get(['r.id_siswa', 'k.kelompok']);

        $requiredDocTypes = [
            'Formulir',
            'Akta Kelahiran',
            'Kartu Keluarga',
            'KTP Ayah',
            'KTP Ibu',
            'Pas Foto Ayah',
            'Pas Foto Ibu',
        ];

        foreach ($sampleStudents as $idx => $student) {
            $targetStatus = match ($idx) {
                0, 1, 2 => 'Diterima',
                3 => 'Diverifikasi',
                default => 'Diajukan',
            };

            $regId = DB::table('pendaftaran')->insertGetId([
                'id_siswa' => $student->id_siswa,
                'id_tahun_ajaran' => $activeYearId,
                'kelompok' => $student->kelompok,
                'status' => 'Diajukan',
                'created_at' => now()->subDays(20 - $idx),
                'updated_at' => now()->subDays(20 - $idx),
            ]);

            foreach ($requiredDocTypes as $docType) {
                DB::table('dokumen_pendaftaran')->insert([
                    'id_pendaftaran' => $regId,
                    'jenis' => $docType,
                    'file_path' => 'pendaftaran/berkas-pendaftaran.pdf',
                    'status_verifikasi' => $targetStatus === 'Diajukan' ? 'Menunggu' : 'Valid',
                    'catatan' => $targetStatus === 'Diajukan' ? null : 'Berkas jelas dan sesuai',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (in_array($targetStatus, ['Diverifikasi', 'Diterima'], true)) {
                DB::table('pendaftaran')->where('id_pendaftaran', $regId)->update([
                    'status' => 'Diverifikasi',
                    'catatan_verifikasi' => 'Seluruh berkas persyaratan telah lengkap dan terverifikasi.',
                    'id_user_verifikator' => $adminUserId,
                    'tanggal_verifikasi' => now()->subDays(10),
                    'updated_at' => now(),
                ]);
            }

            if ($targetStatus === 'Diterima') {
                DB::table('pendaftaran')->where('id_pendaftaran', $regId)->update([
                    'status' => 'Diterima',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedActivitiesAndDocumentation(): void
    {
        if (! Schema::hasTable('dokumentasi_kegiatan') || DB::table('dokumentasi_kegiatan')->count() > 0) {
            return;
        }

        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 1;

        DB::table('kegiatan')
            ->where('jenis', 'Field Trip')
            ->whereNull('biaya')
            ->update(['biaya' => 75000, 'updated_at' => now()]);

        $activities = DB::table('kegiatan')->orderBy('id_kegiatan')->limit(6)->get(['id_kegiatan', 'nama_kegiatan']);
        foreach ($activities as $activity) {
            foreach (['Sebelum', 'Selama', 'Sesudah'] as $tahap) {
                DB::table('dokumentasi_kegiatan')->insert([
                    'id_kegiatan' => $activity->id_kegiatan,
                    'tahap' => $tahap,
                    'tipe' => 'Foto',
                    'file_path' => 'kegiatan/sample-kegiatan.png',
                    'keterangan' => "Dokumentasi tahap {$tahap} kegiatan {$activity->nama_kegiatan}",
                    'id_user_upload' => $adminUserId,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function seedBillingAndPayments(): void
    {
        if (! Schema::hasTable('tagihan') || DB::table('tagihan')->count() > 0) {
            return;
        }

        $activeYearId = DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran') ?? 3;
        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 1;

        $rosters = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->leftJoin('diskon as d', 'd.id_diskon', '=', 'r.id_diskon')
            ->where('k.id_tahun_ajaran', $activeYearId)
            ->where('r.status', 'Aktif')
            ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
            ->orderBy('r.id_rombel')
            ->get(['r.id_rombel', 'k.kelompok', 'd.tipe as diskon_tipe', 'd.nilai as diskon_nilai']);

        $sppTariffs = DB::table('tarif')
            ->where('id_tahun_ajaran', $activeYearId)
            ->where('id_jenis_biaya', 1)
            ->pluck('nominal', 'kelompok');

        foreach (['2026-10-01' => '2026-10-10', '2026-11-01' => '2026-11-10'] as $period => $dueDate) {
            foreach ($rosters as $idx => $roster) {
                $nominal = (float) ($sppTariffs[$roster->kelompok] ?? 350000);
                $discount = 0.0;
                if ($roster->diskon_tipe === 'Persen') {
                    $discount = round($nominal * ((float) $roster->diskon_nilai / 100), 2);
                } elseif ($roster->diskon_tipe === 'Nominal') {
                    $discount = min((float) $roster->diskon_nilai, $nominal);
                }

                $billId = DB::table('tagihan')->insertGetId([
                    'id_rombel' => $roster->id_rombel,
                    'id_jenis_biaya' => 1,
                    'periode' => $period,
                    'nominal' => $nominal,
                    'diskon' => $discount,
                    'jatuh_tempo' => $dueDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $netAmount = $nominal - $discount;

                if ($period === '2026-10-01' || $idx < 15) {
                    $isTransfer = $idx % 2 === 1;
                    $isPending = $period === '2026-11-01' && $idx >= 11 && $idx < 15;

                    $paymentId = DB::table('pembayaran')->insertGetId([
                        'tanggal_bayar' => $period === '2026-10-01' ? '2026-10-08 09:30:00' : '2026-11-07 10:15:00',
                        'metode' => $isTransfer ? 'Transfer' : 'Tunai',
                        'bukti_transfer' => $isTransfer ? 'bukti-pembayaran/sample-bukti.png' : null,
                        'status' => $isPending ? 'Menunggu Verifikasi' : 'Terverifikasi',
                        'catatan' => $isPending ? 'Bukti transfer SPP bulan November' : 'Pembayaran SPP lunas',
                        'id_user_pencatat' => $adminUserId,
                        'id_user_verifikator' => $isPending ? null : $adminUserId,
                        'tanggal_verifikasi' => $isPending ? null : now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('alokasi_pembayaran')->insert([
                        'id_pembayaran' => $paymentId,
                        'id_tagihan' => $billId,
                        'jumlah' => $netAmount,
                    ]);
                }
            }
        }
    }

    private function seedSavings(): void
    {
        if (! Schema::hasTable('transaksi_tabungan') || DB::table('transaksi_tabungan')->count() > 0) {
            return;
        }

        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 1;
        $activeStudentIds = DB::table('rombel_siswa')
            ->where('status', 'Aktif')
            ->orderBy('id_rombel')
            ->limit(20)
            ->pluck('id_siswa')
            ->all();

        foreach ($activeStudentIds as $idx => $studentId) {
            DB::table('transaksi_tabungan')->insert([
                'id_siswa' => $studentId,
                'tanggal' => '2026-10-15 08:30:00',
                'jenis' => 'Setor',
                'jumlah' => 100000 + (($idx % 5) * 50000),
                'metode' => $idx % 2 === 0 ? 'Tunai' : 'Transfer',
                'keterangan' => 'Setoran tabungan awal semester',
                'id_user_pencatat' => $adminUserId,
                'created_at' => now(),
            ]);

            if ($idx % 3 === 0) {
                DB::table('transaksi_tabungan')->insert([
                    'id_siswa' => $studentId,
                    'tanggal' => '2026-11-12 09:00:00',
                    'jenis' => 'Tarik',
                    'jumlah' => 50000,
                    'metode' => 'Tunai',
                    'keterangan' => 'Penarikan tabungan untuk keperluan perlengkapan',
                    'id_user_pencatat' => $adminUserId,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function seedMessages(): void
    {
        if (! Schema::hasTable('messages') || DB::table('messages')->count() > 0) {
            return;
        }

        $adminId = DB::table('users')->where('role', 'admin')->value('id_user');
        $teacherId = DB::table('users')->whereIn('role', ['guru', 'teacher'])->value('id_user');
        $parentId = DB::table('users')->whereIn('role', ['orang_tua', 'parent'])->value('id_user');

        if ($adminId && $parentId) {
            DB::table('messages')->insert([
                [
                    'sender_id' => $parentId,
                    'recipient_id' => $adminId,
                    'body' => 'Assalamualaikum Admin, mohon informasi jadwal pembagian rapor semester ganjil untuk tahun ajaran 2026-2027.',
                    'read_at' => now(),
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
                [
                    'sender_id' => $adminId,
                    'recipient_id' => $parentId,
                    'body' => 'Waalaikumsalam Bapak/Ibu, pembagian rapor Semester I dijadwalkan pada hari Sabtu, 19 Desember 2026 pukul 08.30 WIB di aula sekolah.',
                    'read_at' => null,
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ],
            ]);
        }

        if ($teacherId && $parentId) {
            DB::table('messages')->insert([
                'sender_id' => $teacherId,
                'recipient_id' => $parentId,
                'body' => 'Assalamualaikum Bapak/Ibu, alhamdulillah minggu ini ananda sudah lancar menghafal doa sebelum makan dan doa keluar rumah.',
                'read_at' => null,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ]);
        }
    }

    private function seedAuditLog(): void
    {
        if (! Schema::hasTable('audit_log') || DB::table('audit_log')->count() > 0) {
            return;
        }

        $adminUserId = DB::table('users')->where('role', 'admin')->value('id_user') ?? 9;
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET @current_user_id = ?', [$adminUserId]);
        }

        $pendingPayment = DB::table('pembayaran')
            ->where('status', 'Menunggu Verifikasi')
            ->orderBy('id_pembayaran')
            ->first();
        if ($pendingPayment) {
            DB::table('pembayaran')
                ->where('id_pembayaran', $pendingPayment->id_pembayaran)
                ->update([
                    'status' => 'Terverifikasi',
                    'id_user_verifikator' => $adminUserId,
                    'tanggal_verifikasi' => now(),
                    'catatan' => 'Bukti transfer diverifikasi oleh Administrator.',
                    'updated_at' => now(),
                ]);
        }
    }
}
