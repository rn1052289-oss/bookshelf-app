<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_genre_routes()
    {
        $genre = Genre::factory()->create();

        $this->get('/genres')
            ->assertRedirect('/login');

        $this->get('/genres/create')
            ->assertRedirect('/login');

        $this->post('/genres', [
            'name' => '小説',
        ])->assertRedirect('/login');

        $this->get("/genres/{$genre->id}")
            ->assertRedirect('/login');

        $this->get("/genres/{$genre->id}/edit")
            ->assertRedirect('/login');

        $this->put("/genres/{$genre->id}", [
            'name' => '文学',
        ])->assertRedirect('/login');

        $this->delete("/genres/{$genre->id}")
            ->assertRedirect('/login');
    }

    public function test_genre_list_displays_book_count()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);
        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books);

        $response = $this->actingAs($user)->get('/genres');

        $response->assertStatus(200);
        $response->assertSee('小説');
        $response->assertSee('2冊');
    }

    public function test_genre_detail_books_are_paginated_by_10()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();

        $genre->books()->attach($books);

        $response = $this->actingAs($user)
            ->get("/genres/{$genre->id}");

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->perPage() === 10
                && $books->total() === 11
                && $books->count() === 10;
        });
    }

    public function test_can_create_genre()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => '小説',
        ]);

        $response->assertRedirect('/genres');
        $response->assertSessionHas('success', 'ジャンルを登録しました。');

        $this->assertDatabaseHas('genres', [
            'name' => '小説',
        ]);
    }

    public function test_cannot_create_duplicate_genre_name()
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->from('/genres/create')
            ->post('/genres', [
                'name' => '小説',
            ]);

        $response->assertRedirect('/genres/create');
        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_can_update_genre()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->put("/genres/{$genre->id}", [
                'name' => '文学',
            ]);

        $response->assertRedirect('/genres');
        $response->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '文学',
        ]);
    }

    public function test_cannot_delete_genre_with_books()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book);

        $response = $this->actingAs($user)
            ->delete("/genres/{$genre->id}");

        $response->assertRedirect('/genres');
        $response->assertSessionHas(
            'error',
            'このジャンルには書籍が紐付いているため削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_can_delete_genre_without_books()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->delete("/genres/{$genre->id}");

        $response->assertRedirect('/genres');
        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }
}
