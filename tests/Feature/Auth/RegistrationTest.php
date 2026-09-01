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
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('ログイン');
        $response->assertSee('href="'.route('login').'"', false);
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

        $response->assertRedirect('/');
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

    /**
     * 会員登録のバリデーションエラーが日本語で表示されることを確認する。
     */
    public function test_registration_validation_errors_are_displayed_in_japanese(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'name' => '名前は必須です。',
            'email' => 'メールアドレスは必須です。',
            'password' => 'パスワードは必須です。',
        ]);
    }

    /**
     * パスワードが8文字未満の場合は登録できないことを確認する。
     */
    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください。',
        ]);
    }

    /**
     * パスワードが255文字を超える場合は登録できないことを確認する。
     */
    public function test_password_cannot_exceed_255_characters(): void
    {
        $password = str_repeat('a', 256);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors([
            'password' => 'パスワードは255文字以下で入力してください。',
        ]);
    }
}
