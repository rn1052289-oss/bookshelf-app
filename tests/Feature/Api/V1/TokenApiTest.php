<?php

namespace Tests\Feature\Api\V1;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_issue_api_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'api-token',
        ]);
    }

    public function test_returns_401_when_password_is_invalid()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => '認証が必要です。',
        ]);
    }

    public function test_returns_401_when_email_does_not_exist()
    {
        $response = $this->postJson('/api/v1/tokens', [
            'email' => 'not-found@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized',
            'message' => '認証が必要です。',
        ]);
    }

    public function test_can_use_issued_bearer_token_to_create_book()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::factory()->create();

        $tokenResponse = $this->postJson('/api/v1/tokens', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $tokenResponse->json('token');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/books', [
                'title' => 'Bearer Tokenテスト',
                'author' => 'テスト著者',
                'isbn' => '9784000000070',
                'genre_ids' => [$genre->id],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('books', [
            'title' => 'Bearer Tokenテスト',
        ]);
    }
}
