<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kelas') || ! Schema::hasTable('guru') || ! Schema::hasTable('guru_kelas') || ! Schema::hasTable('tahun_ajaran')) {
            return;
        }

        $activeYearId = DB::table('tahun_ajaran')->where('is_aktif', true)->value('id_tahun_ajaran');
        if (! $activeYearId) {
            return;
        }

        $officialClasses = [
            ['nama_kelas' => 'PG', 'kelompok' => 'KB', 'kapasitas' => 15, 'wali' => 'Nur Balqis', 'pendamping' => 'Rauzatul Maulida'],
            ['nama_kelas' => 'Abu Bakar', 'kelompok' => 'TK A', 'kapasitas' => 12, 'wali' => 'Chairunnisa', 'pendamping' => null],
            ['nama_kelas' => 'Ar Rahim', 'kelompok' => 'TK A', 'kapasitas' => 12, 'wali' => 'Lismainur', 'pendamping' => null],
            ['nama_kelas' => 'Marwah', 'kelompok' => 'TK B', 'kapasitas' => 12, 'wali' => 'Lidya Wuri', 'pendamping' => null],
            ['nama_kelas' => 'Safa', 'kelompok' => 'TK B', 'kapasitas' => 12, 'wali' => 'Mardiani', 'pendamping' => null],
            ['nama_kelas' => 'Jannah', 'kelompok' => 'TK B', 'kapasitas' => 12, 'wali' => 'Puput Syaputri', 'pendamping' => null],
        ];

        foreach ($officialClasses as $spec) {
            $classId = DB::table('kelas')
                ->where('id_tahun_ajaran', $activeYearId)
                ->where('nama_kelas', $spec['nama_kelas'])
                ->value('id_kelas');

            if (! $classId) {
                continue;
            }

            if (Schema::hasColumn('kelas', 'kapasitas')) {
                DB::table('kelas')->where('id_kelas', $classId)->update([
                    'kelompok' => $spec['kelompok'],
                    'kapasitas' => $spec['kapasitas'],
                ]);
            }

            $waliId = DB::table('guru')->where('nama_lengkap', 'like', '%'.$spec['wali'].'%')->value('id_guru');
            if ($waliId) {
                DB::table('guru_kelas')->where('id_kelas', $classId)->where('peran', 'Wali Kelas')->delete();
                DB::table('guru_kelas')->updateOrInsert(
                    ['id_kelas' => $classId, 'id_guru' => $waliId],
                    ['peran' => 'Wali Kelas']
                );
            }

            if ($spec['pendamping']) {
                $pendampingId = DB::table('guru')->where('nama_lengkap', 'like', '%'.$spec['pendamping'].'%')->value('id_guru');
                if ($pendampingId) {
                    DB::table('guru_kelas')->updateOrInsert(
                        ['id_kelas' => $classId, 'id_guru' => $pendampingId],
                        ['peran' => 'Pendamping']
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Non-destructive data alignment
    }
};
