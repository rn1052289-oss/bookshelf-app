<?php

namespace Tests\Feature\Favorite;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from("/books/{$book->id}")
            ->post("/books/{$book->id}/favorites");

        $response->assertRedirect("/books/{$book->id}");

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_remove_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->from("/books/{$book->id}")
            ->post("/books/{$book->id}/favorites");

        $response->assertRedirect("/books/{$book->id}");

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_add_favorite_again_after_removing_it(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post("/books/{$book->id}/favorites");

        $this->actingAs($user)
            ->post("/books/{$book->id}/favorites");

        $this->actingAs($user)
            ->post("/books/{$book->id}/favorites");

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_user_can_favorite_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/books/{$book->id}/favorites");

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_can_see_only_own_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $favoriteBook = Book::factory()->create([
            'title' => '自分のお気に入り書籍',
        ]);

        $otherFavoriteBook = Book::factory()->create([
            'title' => '他人のお気に入り書籍',
        ]);

        $user->favoriteBooks()->attach($favoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherFavoriteBook->id);

        $response = $this
            ->actingAs($user)
            ->get('/favorites');

        $response->assertStatus(200);
        $response->assertSee('自分のお気に入り書籍');
        $response->assertDontSee('他人のお気に入り書籍');
    }

    public function test_favorites_are_paginated_by_10(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        $response = $this
            ->actingAs($user)
            ->get('/favorites');

        $response->assertStatus(200);

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
                && $books->total() === 11;
        });
    }

    public function test_guest_cannot_view_favorites(): void
    {
        $response = $this->get('/favorites');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post("/books/{$book->id}/favorites");

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('favorites', 0);
    }
}
