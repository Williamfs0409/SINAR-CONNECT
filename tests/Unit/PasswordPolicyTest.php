<?php

namespace Tests\Unit;

use App\Support\PasswordPolicy;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_eight_character_password_with_letters_and_numbers_is_valid_without_symbols(): void
    {
        $this->assertTrue(PasswordPolicy::accepts('Abcd1234'));
        $this->assertTrue(PasswordPolicy::accepts('Abcd12!4'));
    }

    public function test_password_policy_rejects_short_or_incomplete_passwords(): void
    {
        $this->assertFalse(PasswordPolicy::accepts('Abcd123'));
        $this->assertFalse(PasswordPolicy::accepts('abcdefgh'));
    }
}
