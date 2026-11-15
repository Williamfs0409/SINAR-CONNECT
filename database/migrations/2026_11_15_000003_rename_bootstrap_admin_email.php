<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldEmail = 'admintrator@tkcerdasceria.com';
        $newEmail = 'administrator@tkcerdasceria.com';
        $admin = DB::table('users')
            ->where('email', $oldEmail)
            ->first(['id_user', 'role']);

        if (! $admin) {
            return;
        }

        if ($admin->role !== 'admin') {
            throw new RuntimeException('Refusing to rename the email of a non-admin account.');
        }

        if (DB::table('users')->where('email', $newEmail)->exists()) {
            throw new RuntimeException('Cannot rename the administrator email because the new address is already in use.');
        }

        DB::table('users')
            ->where('id_user', $admin->id_user)
            ->update([
                'email' => $newEmail,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the corrected account email when rolling back unrelated schema changes.
    }
};
