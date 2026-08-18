<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン画面を表示できることを確認する。
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('会員登録');
        $response->assertSee('href="'.route('register').'"', false);
    }

    /**
     * 正しい認証情報でログインできることを確認する。
     */
    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * パスワードが誤っている場合はログインできないことを確認する。
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors([
            'email' => '認証情報が正しくありません。',
        ]);
    }

    /**
     * ログイン済みユーザーがログアウトできることを確認する。
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout');

        $this->assertGuest();
    }

    /**
     * ログイン済みユーザーはログイン画面へアクセスできないことを確認する。
     */
    public function test_authenticated_users_are_redirected_from_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/');
    }

    /**
     * ログイン済みユーザーは会員登録画面へアクセスできないことを確認する。
     */
    public function test_authenticated_users_are_redirected_from_registration_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/register');

        $response->assertRedirect('/');
    }

    /**
     * メールアドレスとパスワードが未入力の場合はログインできないことを確認する。
     */
    public function test_email_and_password_are_required_for_login(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);
    }
}
