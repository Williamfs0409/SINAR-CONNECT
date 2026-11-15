<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.bootstrap_email');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_BOOTSTRAP_EMAIL must be a valid email address.');
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if ($existingUser->role !== 'admin') {
                throw new RuntimeException('The bootstrap email already belongs to a non-admin account.');
            }

            $this->command?->info('Administrator account already exists; no changes were made.');

            return;
        }

        $password = config('admin.bootstrap_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_BOOTSTRAP_PASSWORD to a unique password of at least 12 characters.');
        }

        $user = User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);
        $user->is_active = true;
        $user->save();

        $this->command?->info("Administrator account created: {$email}");
    }
}
