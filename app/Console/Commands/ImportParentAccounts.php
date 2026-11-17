<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ImportParentAccounts extends Command
{
    protected $signature = 'app:import-parent-accounts
        {file : Path to a CSV with id_keluarga,email,kata_sandi columns}
        {--execute : Create new accounts and link them to families}';

    protected $description = 'Validate or import parent accounts from a CSV file.';

    public function handle(): int
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasTable('keluarga')
            || ! Schema::hasTable('siswa')
            || ! Schema::hasColumn('keluarga', 'id_user')
            || ! Schema::hasColumn('siswa', 'id_keluarga')) {
            $this->error('The users, keluarga, and siswa tables must be available with the expected columns.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', 'must_change_password')) {
            $this->error('Apply the additive password-security migration before importing parent accounts.');

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows((string) $this->argument('file'));
            [$created, $skipped] = DB::transaction(function () use ($rows): array {
                $toCreate = [];
                $skipped = 0;

                foreach ($rows as $row) {
                    $family = DB::table('keluarga')
                        ->where('id_keluarga', $row['family_id'])
                        ->lockForUpdate()
                        ->first(['id_user']);

                    if ($family === null) {
                        throw new InvalidArgumentException("Family ID {$row['family_id']} was not found.");
                    }

                    if (! DB::table('siswa')->where('id_keluarga', $row['family_id'])->exists()) {
                        throw new InvalidArgumentException("Family ID {$row['family_id']} has no student records.");
                    }

                    if ($family->id_user !== null) {
                        $linkedUser = DB::table('users')
                            ->where('id_user', $family->id_user)
                            ->first(['email', 'role', 'is_active']);

                        if ($linkedUser !== null
                            && $linkedUser->role === 'orang_tua'
                            && (bool) $linkedUser->is_active
                            && mb_strtolower($linkedUser->email) === $row['email']) {
                            $skipped++;

                            continue;
                        }

                        throw new InvalidArgumentException("Family ID {$row['family_id']} is already linked to another account.");
                    }

                    $existingUser = DB::table('users')
                        ->whereRaw('LOWER(email) = ?', [$row['email']])
                        ->exists();
                    if ($existingUser) {
                        throw new InvalidArgumentException("Email conflict found for family ID {$row['family_id']}.");
                    }

                    $toCreate[] = $row;
                }

                if ($this->option('execute')) {
                    foreach ($toCreate as $row) {
                        $parent = User::create([
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => $row['password'],
                            'role' => 'parent',
                            'must_change_password' => true,
                        ]);

                        $linked = DB::table('keluarga')
                            ->where('id_keluarga', $row['family_id'])
                            ->whereNull('id_user')
                            ->update([
                                'id_user' => $parent->getKey(),
                                'updated_at' => now(),
                            ]);

                        if ($linked !== 1) {
                            throw new InvalidArgumentException("Family ID {$row['family_id']} could not be linked.");
                        }
                    }
                }

                return [count($toCreate), $skipped];
            });
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('execute')) {
            $this->info("Dry run complete: {$created} new account(s) ready; {$skipped} already-linked account(s) left unchanged.");
            $this->comment('No database records were changed. Add --execute to perform the import.');

            return self::SUCCESS;
        }

        $this->info("Import complete: {$created} account(s) created; {$skipped} already-linked account(s) left unchanged.");

        return self::SUCCESS;
    }

    private function readRows(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('The CSV file does not exist or cannot be read.');
        }

        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new InvalidArgumentException('The CSV file could not be opened.');
        }

        try {
            $header = fgetcsv($file, 0, ',', '"', '');
            if ($header === false) {
                throw new InvalidArgumentException('The CSV file is empty.');
            }

            $header = array_map('trim', $header);
            $header[0] = ltrim($header[0] ?? '', "\xEF\xBB\xBF");
            $hasParentDetails = $header === [
                'id_keluarga', 'nama_orang_tua', 'hubungan', 'email', 'kata_sandi',
            ];
            if (! $hasParentDetails && $header !== ['id_keluarga', 'email', 'kata_sandi']) {
                throw new InvalidArgumentException(
                    'CSV headers must be id_keluarga,email,kata_sandi or id_keluarga,nama_orang_tua,hubungan,email,kata_sandi.'
                );
            }

            $rows = [];
            $familyIds = [];
            $emails = [];
            $line = 1;

            while (($fields = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $line++;

                if ($fields === [null] || count(array_filter($fields, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $expectedColumns = $hasParentDetails ? 5 : 3;
                if (count($fields) !== $expectedColumns) {
                    throw new InvalidArgumentException("CSV line {$line} must contain exactly {$expectedColumns} columns.");
                }

                $familyId = filter_var(trim((string) $fields[0]), FILTER_VALIDATE_INT);
                $name = $hasParentDetails
                    ? trim((string) $fields[1])
                    : "Orang Tua Keluarga {$familyId}";
                $emailIndex = $hasParentDetails ? 3 : 1;
                $email = mb_strtolower(trim((string) $fields[$emailIndex]));
                $password = (string) $fields[$emailIndex + 1];

                if ($familyId === false || $familyId < 1) {
                    throw new InvalidArgumentException("CSV line {$line} has an invalid family ID.");
                }

                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 150) {
                    throw new InvalidArgumentException("CSV line {$line} has an invalid email.");
                }

                if ($name === '' || mb_strlen($name) > 100) {
                    throw new InvalidArgumentException("CSV line {$line} has an invalid parent name.");
                }

                if (trim($password) === '') {
                    throw new InvalidArgumentException("CSV line {$line} has an empty password.");
                }

                if (isset($familyIds[$familyId])) {
                    throw new InvalidArgumentException("Family ID {$familyId} appears more than once in the CSV.");
                }

                if (isset($emails[$email])) {
                    throw new InvalidArgumentException("Email appears more than once in the CSV on line {$line}.");
                }

                $familyIds[$familyId] = true;
                $emails[$email] = true;
                $rows[] = [
                    'family_id' => $familyId,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                ];
            }

            if ($rows === []) {
                throw new InvalidArgumentException('The CSV contains no parent account rows.');
            }

            return $rows;
        } finally {
            fclose($file);
        }
    }
}
