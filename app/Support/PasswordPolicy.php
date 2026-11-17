<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    public const MESSAGE = 'Kata sandi minimal 8 karakter dan harus memuat huruf besar, huruf kecil, serta angka. Simbol tidak wajib.';

    public static function rule(): Password
    {
        return Password::min(8)->letters()->mixedCase()->numbers();
    }

    public static function accepts(mixed $password): bool
    {
        return is_string($password)
            && Validator::make(['password' => $password], ['password' => [self::rule()]])->passes();
    }
}
