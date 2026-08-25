<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-08-18',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9784000000000',
        ]);

        $book = Book::where('isbn', '9784000000000')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_guest_cannot_create_book()
    {
        $genre = Genre::factory()->create();

        $response = $this->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-08-18',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000001',
        ]);
    }

    public function test_book_validation_errors_are_displayed_in_japanese()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => '',
            'author' => '',
            'isbn' => '123',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
        ]);

        $this->assertEquals(
            'タイトルは必須です。',
            session('errors')->first('title')
        );

        $this->assertEquals(
            '著者名は必須です。',
            session('errors')->first('author')
        );

        $this->assertEquals(
            'ISBNは13桁の数字で入力してください。',
            session('errors')->first('isbn')
        );
    }

    public function test_owner_can_update_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->sync([$oldGenre->id]);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-08-18',
            'description' => '更新後の説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_other_user_cannot_update_book()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title' => '不正な更新',
            'author' => '不正な著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-08-18',
            'description' => '不正な更新',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '不正な更新',
        ]);
    }

    public function test_other_user_cannot_delete_book()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->delete("/books/{$book->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_duplicate_isbn_cannot_be_created()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '9784000000002',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '重複ISBN書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertEquals(
            'このISBNはすでに登録されています。',
            session('errors')->first('isbn')
        );
    }

    public function test_owner_can_update_book_with_same_isbn()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('isbn');
    }

    public function test_owner_can_delete_book_with_related_data()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);
        $book->favoritedByUsers()->attach($user->id);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $review->likedByUsers()->attach($user->id);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)->delete("/books/{$book->id}");

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'book_id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_access_book_edit_page()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->get("/books/{$book->id}/edit");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_book_create_page()
    {
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }
}
