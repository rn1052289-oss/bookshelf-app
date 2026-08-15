<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録画面を表示できることを確認する。
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    /**
     * 正常な情報でユーザー登録できることを確認する。
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Issue3 Test User',
            'email' => 'issue3-test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'Issue3 Test User',
            'email' => 'issue3-test@example.com',
        ]);
    }

    /**
     * 重複したメールアドレスでは登録できないことを確認する。
     */
    public function test_users_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            User::where('email', 'duplicate@example.com')->count()
        );
    }
}
