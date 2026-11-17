<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ParentAccountsSeeder extends Seeder
{
    private const ACCOUNTS = [
        1 => 'harissabri@gmail.com',
        2 => 'raihansyahht@gmail.com',
        3 => 'rezaafrizal@gmail.com',
        4 => 'safriandiberutu@gmail.com',
        5 => 'sidiqpramudyaningtias@gmail.com',
        6 => 'hadikurnia@gmail.com',
        7 => 'ediajuan@gmail.com',
        8 => 'diki@gmail.com',
        9 => 'ramadhansyah@gmail.com',
        10 => 'naslin@gmail.com',
        11 => 'fadhli@gmail.com',
        12 => 'bramilhani@gmail.com',
        13 => 'agungwahyudi@gmail.com',
        14 => 'ferihadianto@gmail.com',
        15 => 'firdaussibarani@gmail.com',
        16 => 'ronitua@gmail.com',
        17 => 'khairulikhsan@gmail.com',
        18 => 'ramli@gmail.com',
        19 => 'bobbyanugerah@gmail.com',
        20 => 'abdulharis@gmail.com',
        21 => 'saptahadi@gmail.com',
        22 => 'muhammadnur@gmail.com',
        23 => 'johanaladin@gmail.com',
        24 => 'heriyawanandri@gmail.com',
        25 => 'daud@gmail.com',
        26 => 'rizalluthfi@gmail.com',
        27 => 'arrywahyudi@gmail.com',
        28 => 'ahmadromansyah@gmail.com',
        29 => 'atsarinaawanis@gmail.com',
        30 => 'ariefananda@gmail.com',
        31 => 'bennyarianto@gmail.com',
        32 => 'muhammadhaikal@gmail.com',
        33 => 'perdipradana@gmail.com',
        34 => 'sutantua@gmail.com',
        35 => 'safriwahidi@gmail.com',
        36 => 'hotmaindra@gmail.com',
        37 => 'rizkyrestu@gmail.com',
        39 => 'khadaviibrahim@gmail.com',
        40 => 'imampranata@gmail.com',
        41 => 'ramasanjaya@gmail.com',
        42 => 'bambangfajar@gmail.com',
        43 => 'ridwan@gmail.com',
        44 => 'dedijuanda@gmail.com',
        45 => 'muhammadabduh@gmail.com',
        46 => 'juliansyahputra@gmail.com',
        47 => 'viskiumar@gmail.com',
        48 => 'ikrimahhamidy@gmail.com',
        49 => 'harsyahtriputra@gmail.com',
        50 => 'rezafoureza@gmail.com',
        51 => 'edupradana@gmail.com',
        52 => 'saidibrahim@gmail.com',
        53 => 'rezaaulia@gmail.com',
        54 => 'muhammadnasir@gmail.com',
        55 => 'syukurabdullah@gmail.com',
        56 => 'muhammadgunawan@gmail.com',
        57 => 'amriramadhan@gmail.com',
        58 => 'danielginting@gmail.com',
        59 => 'muhammadfauzan@gmail.com',
        60 => 'budiman@gmail.com',
        61 => 'viskiumar61@gmail.com',
        62 => 'fransaffandhi@gmail.com',
        63 => 'rasyid@gmail.com',
        64 => 'surianditarigan@gmail.com',
        66 => 'awaluddinakbar@gmail.com',
        79 => 'saidibrahim79@gmail.com',
        82 => 'ekoandriansyah@gmail.com',
        86 => 'kharrulikhsan@gmail.com',
        88 => 'topowibowo@gmail.com',
        89 => 'elwin@gmail.com',
        90 => 'risqiananda@gmail.com',
        91 => 'susanto@gmail.com',
        92 => 'alimuhammad@gmail.com',
        93 => 'herryamran@gmail.com',
        94 => 'herryamran94@gmail.com',
        95 => 'abdigunawan@gmail.com',
        96 => 'rahmatasfari@gmail.com',
        97 => 'muhammadtaufik@gmail.com',
        98 => 'fahrurrozy@gmail.com',
        99 => 'maulanaichsan@gmail.com',
        100 => 'dimasagung@gmail.com',
        110 => 'harunal@gmail.com',
        112 => 'kharismasyahputra@gmail.com',
        115 => 'dianpratama@gmail.com',
        116 => 'herryamran116@gmail.com',
        117 => 'muhammadkevin@gmail.com',
        119 => 'junaidy@gmail.com',
        120 => 'arifwibowo@gmail.com',
        121 => 'jepriapriza@gmail.com',
        122 => 'firdausrinaldy@gmail.com',
        123 => 'desjulhamzah@gmail.com',
        124 => 'dzakiihsan@gmail.com',
        125 => 'rizamaulana@gmail.com',
        126 => 'rizkyfernanda@gmail.com',
    ];

    public function run(): void
    {
        $password = config('admin.parent_bootstrap_password');
        if (! PasswordPolicy::accepts($password)) {
            throw new RuntimeException('Set PARENT_BOOTSTRAP_PASSWORD to a password of at least 8 characters with uppercase and lowercase letters and a number; symbols are optional.');
        }

        if (! Schema::hasTable('users')
            || ! Schema::hasTable('keluarga')
            || ! Schema::hasTable('siswa')
            || ! Schema::hasColumn('keluarga', 'id_user')
            || ! Schema::hasColumn('siswa', 'id_keluarga')
            || ! Schema::hasColumn('users', 'must_change_password')) {
            throw new RuntimeException('Apply the required database migrations before running this seeder.');
        }

        [$createdCount, $skippedFamilyIds] = DB::transaction(function () use ($password): array {
            $toCreate = [];
            $skippedFamilyIds = [];

            foreach (self::ACCOUNTS as $familyId => $email) {
                $email = mb_strtolower($email);
                $family = DB::table('keluarga')
                    ->where('id_keluarga', $familyId)
                    ->lockForUpdate()
                    ->first(['id_user']);

                if ($family === null) {
                    throw new RuntimeException("Family ID {$familyId} was not found.");
                }

                if (! DB::table('siswa')->where('id_keluarga', $familyId)->exists()) {
                    throw new RuntimeException("Family ID {$familyId} has no student records.");
                }

                if ($family->id_user !== null) {
                    $linkedUser = DB::table('users')
                        ->where('id_user', $family->id_user)
                        ->first(['email', 'role', 'is_active']);

                    if ($linkedUser !== null
                        && $linkedUser->role === 'orang_tua'
                        && (bool) $linkedUser->is_active
                        && mb_strtolower($linkedUser->email) === $email) {
                        $skippedFamilyIds[] = $familyId;

                        continue;
                    }

                    throw new RuntimeException("Family ID {$familyId} is already linked to another account.");
                }

                if (DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    throw new RuntimeException("Email conflict found for family ID {$familyId}.");
                }

                $toCreate[] = [
                    'family_id' => $familyId,
                    'name' => $this->parentName($familyId),
                    'email' => $email,
                ];
            }

            foreach ($toCreate as $account) {
                $parent = User::create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $password,
                    'role' => 'parent',
                    'must_change_password' => true,
                ]);

                $linked = DB::table('keluarga')
                    ->where('id_keluarga', $account['family_id'])
                    ->whereNull('id_user')
                    ->update([
                        'id_user' => $parent->getKey(),
                        'updated_at' => now(),
                    ]);

                if ($linked !== 1) {
                    throw new RuntimeException("Family ID {$account['family_id']} could not be linked.");
                }
            }

            return [count($toCreate), $skippedFamilyIds];
        });

        $this->command?->info("Created {$createdCount} parent account(s).");

        if ($skippedFamilyIds !== []) {
            $this->command?->info(
                'Already-linked parent accounts left unchanged for family IDs: '.implode(', ', $skippedFamilyIds)
            );
        }
    }

    private function parentName(int $familyId): string
    {
        if (Schema::hasTable('wali')) {
            $name = DB::table('wali')
                ->where('id_keluarga', $familyId)
                ->orderByRaw("CASE hubungan WHEN 'Ayah' THEN 0 WHEN 'Ibu' THEN 1 ELSE 2 END")
                ->value('nama_lengkap');

            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return "Orang Tua Keluarga {$familyId}";
    }
}
