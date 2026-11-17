<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TeacherAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('admin.teacher_bootstrap_password');
        if (! PasswordPolicy::accepts($password)) {
            throw new RuntimeException('Set TEACHER_BOOTSTRAP_PASSWORD to a password of at least 8 characters with uppercase and lowercase letters and a number; symbols are optional.');
        }

        [$updatedCount, $skippedUserIds] = DB::transaction(function () use ($password): array {
            $teacherUserIds = DB::table('guru')
                ->whereNotNull('id_user')
                ->distinct()
                ->pluck('id_user');

            if ($teacherUserIds->isEmpty()) {
                throw new RuntimeException('No teacher accounts linked to guru records were found.');
            }

            $updatedCount = 0;
            $skippedUserIds = [];

            foreach ($teacherUserIds as $userId) {
                $user = User::find($userId);
                if (! $user) {
                    throw new RuntimeException("No user account found for teacher user ID {$userId}.");
                }
                if (! in_array($user->role, ['teacher', 'principal'], true)) {
                    $skippedUserIds[] = $userId;

                    continue;
                }

                $user->password = $password;
                $user->is_active = true;
                $user->save();
                $updatedCount++;
            }

            if ($updatedCount === 0) {
                throw new RuntimeException('No linked teacher accounts have the teacher role.');
            }

            return [$updatedCount, $skippedUserIds];
        });

        $this->command?->info("Updated {$updatedCount} teacher and principal accounts.");

        if ($skippedUserIds !== []) {
            $this->command?->warn(
                'Skipped linked user IDs with unrelated roles: '.implode(', ', $skippedUserIds)
            );
        }
    }
}
