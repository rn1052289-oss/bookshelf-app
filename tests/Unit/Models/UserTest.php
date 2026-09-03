<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_fillable_attributes_are_correct(): void
    {
        $user = new User;

        $this->assertSame([
            'name',
            'email',
            'password',
        ], $user->getFillable());
    }

    public function test_hidden_attributes_are_correct(): void
    {
        $user = new User;

        $this->assertSame([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    public function test_casts_are_correct(): void
    {
        $user = new User;
        $casts = $user->getCasts();

        $this->assertSame('datetime', $casts['email_verified_at']);
        $this->assertSame('hashed', $casts['password']);
    }
}
