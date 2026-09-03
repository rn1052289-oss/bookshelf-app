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

        $this->get('/genres')->assertRedirect('/login');
        $this->get('/genres/create')->assertRedirect('/login');

        $this->post('/genres', [
            'name' => '小説',
        ])->assertRedirect('/login');

        $this->get("/genres/{$genre->id}")->assertRedirect('/login');
        $this->get("/genres/{$genre->id}/edit")->assertRedirect('/login');

        $this->put("/genres/{$genre->id}", [
            'name' => '文学',
        ])->assertRedirect('/login');

        $this->delete("/genres/{$genre->id}")->assertRedirect('/login');
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
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            $viewGenre = $genres->firstWhere('id', $genre->id);

            return $viewGenre !== null && $viewGenre->books_count === 2;
        });
        $response->assertSee('小説');
        $response->assertSee('2冊');
    }

    public function test_genre_detail_books_are_paginated_by_10()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();

        $genre->books()->attach($books);

        $response = $this->actingAs($user)->get("/genres/{$genre->id}");

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

        $response->assertRedirect(route('genres.index'));
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
        $response->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています。']);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_can_update_genre()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put("/genres/{$genre->id}", [
            'name' => '文学',
        ]);

        $response->assertRedirect(route('genres.index'));
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

        $response = $this->actingAs($user)->delete("/genres/{$genre->id}");

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('error', 'このジャンルには書籍が紐付いているため削除できません。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_can_delete_genre_without_books()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->delete("/genres/{$genre->id}");

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_authenticated_user_can_access_genre_create_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/genres/create');

        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    public function test_authenticated_user_can_access_genre_edit_page()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get("/genres/{$genre->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre', function ($viewGenre) use ($genre) {
            return $viewGenre->is($genre);
        });
    }

    public function test_genre_name_is_required_when_creating_genre()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_cannot_exceed_255_characters_when_creating_genre()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_is_required_when_updating_genre()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put("/genres/{$genre->id}", [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name' => 'ジャンル名は必須です。']);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_name_cannot_exceed_255_characters_when_updating_genre()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put("/genres/{$genre->id}", [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors(['name' => 'ジャンル名は255文字以内で入力してください。']);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_cannot_update_genre_with_another_genres_name()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        Genre::factory()->create([
            'name' => '文学',
        ]);

        $response = $this->actingAs($user)->put("/genres/{$genre->id}", [
            'name' => '文学',
        ]);

        $response->assertSessionHasErrors(['name' => 'このジャンル名はすでに登録されています。']);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_can_update_genre_with_same_name()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put("/genres/{$genre->id}", [
            'name' => '小説',
        ]);

        $response->assertSessionDoesntHaveErrors('name');
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_nonexistent_genre_returns_404_on_detail_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/genres/999999');

        $response->assertStatus(404);
    }

    public function test_nonexistent_genre_returns_404_on_edit_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/genres/999999/edit');

        $response->assertStatus(404);
    }

    public function test_nonexistent_genre_returns_404_when_updating()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/genres/999999', [
            'name' => '文学',
        ]);

        $response->assertStatus(404);
    }

    public function test_nonexistent_genre_returns_404_when_deleting()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/genres/999999');

        $response->assertStatus(404);
    }
}
