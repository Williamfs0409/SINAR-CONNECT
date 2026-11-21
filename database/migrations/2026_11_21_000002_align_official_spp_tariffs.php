<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tarif') || ! Schema::hasTable('jenis_biaya')) {
            return;
        }

        $sppFeeTypeIds = DB::table('jenis_biaya')
            ->where('kategori', 'Bulanan')
            ->pluck('id_jenis_biaya')
            ->all();

        if ($sppFeeTypeIds === []) {
            return;
        }

        // Sesuai data resmi sekolah: PG/KB = Rp 350.000, TK A & TK B = Rp 250.000
        DB::table('tarif')
            ->whereIn('id_jenis_biaya', $sppFeeTypeIds)
            ->where('kelompok', 'KB')
            ->update(['nominal' => 350000.00]);

        DB::table('tarif')
            ->whereIn('id_jenis_biaya', $sppFeeTypeIds)
            ->whereIn('kelompok', ['TK A', 'TK B'])
            ->update(['nominal' => 250000.00]);
    }

    public function down(): void
    {
        // Non-destructive data alignment
    }
};
