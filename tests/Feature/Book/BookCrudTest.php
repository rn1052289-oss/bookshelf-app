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

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9784000000000',
        ]);

        $book = Book::where('isbn', '9784000000000')->firstOrFail();

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を登録しました。');

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
            'title' => 'タイトルは必須です。',
            'author' => '著者名は必須です。',
            'isbn' => 'ISBNは13桁の数字で入力してください。',
        ]);
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

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を更新しました。');

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

        $response->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています。']);
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

        $response = $this->actingAs($user)->delete("/books/{$book->id}");

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');

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

    public function test_authenticated_user_can_access_book_create_page()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });
    }

    public function test_owner_can_access_book_edit_page()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->get("/books/{$book->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('books.edit');
        $response->assertViewHas('book', function ($viewBook) use ($book) {
            return $viewBook->is($book);
        });
        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });
    }

    public function test_genre_is_required_when_creating_book()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000010',
        ]);

        $response->assertSessionHasErrors(['genres' => 'ジャンルを1件以上選択してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000010',
        ]);
    }

    public function test_nonexistent_genre_is_rejected_when_creating_book()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000011',
            'genres' => [999999],
        ]);

        $response->assertSessionHasErrors(['genres.0' => '選択されたジャンルが存在しません。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000011',
        ]);
    }

    public function test_invalid_published_date_is_rejected_when_creating_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000012',
            'published_date' => 'invalid-date',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['published_date' => '出版日は正しい日付で入力してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000012',
        ]);
    }

    public function test_invalid_image_url_is_rejected_when_creating_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000013',
            'image_url' => 'invalid-url',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['image_url' => '画像URLは正しいURL形式で入力してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000013',
        ]);
    }

    public function test_title_cannot_exceed_255_characters_when_creating_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => str_repeat('a', 256),
            'author' => 'テスト著者',
            'isbn' => '9784000000014',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['title' => 'タイトルは255文字以内で入力してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000014',
        ]);
    }

    public function test_author_cannot_exceed_255_characters_when_creating_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => str_repeat('a', 256),
            'isbn' => '9784000000015',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['author' => '著者名は255文字以内で入力してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000015',
        ]);
    }

    public function test_image_url_cannot_exceed_255_characters_when_creating_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000016',
            'image_url' => 'https://example.com/'.str_repeat('a', 240),
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['image_url' => '画像URLは255文字以内で入力してください。']);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000016',
        ]);
    }

    public function test_owner_cannot_update_book_with_another_books_isbn()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'isbn' => '9784000000020',
        ]);
        $otherBook = Book::factory()->create([
            'isbn' => '9784000000021',
        ]);
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $otherBook->isbn,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['isbn' => 'このISBNはすでに登録されています。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9784000000020',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'isbn' => '9784000000021',
        ]);
    }

    public function test_nonexistent_genre_is_rejected_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'genres' => [999999],
        ]);

        $response->assertSessionHasErrors(['genres.0' => '選択されたジャンルが存在しません。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_invalid_published_date_is_rejected_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
            'published_date' => '2026-08-18',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => 'invalid-date',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['published_date' => '出版日は正しい日付で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'published_date' => '2026-08-18',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_invalid_image_url_is_rejected_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
            'image_url' => 'https://example.com/original.jpg',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'image_url' => 'invalid-url',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['image_url' => '画像URLは正しいURL形式で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'image_url' => 'https://example.com/original.jpg',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_guest_cannot_access_book_edit_page()
    {
        $book = Book::factory()->create();

        $response = $this->get("/books/{$book->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_update_book()
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_guest_cannot_delete_book()
    {
        $book = Book::factory()->create();

        $response = $this->delete("/books/{$book->id}");

        $response->assertRedirect('/login');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_nonexistent_book_returns_404_on_detail_page()
    {
        $response = $this->get('/books/999999');

        $response->assertStatus(404);
    }

    public function test_nonexistent_book_returns_404_on_edit_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/999999/edit');

        $response->assertStatus(404);
    }

    public function test_nonexistent_book_returns_404_when_updating()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put('/books/999999', [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000099',
            'genres' => [$genre->id],
        ]);

        $response->assertStatus(404);
    }

    public function test_nonexistent_book_returns_404_when_deleting()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/books/999999');

        $response->assertStatus(404);
    }

    public function test_genre_is_required_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
        ]);

        $response->assertSessionHasErrors(['genres' => 'ジャンルを1件以上選択してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_title_cannot_exceed_255_characters_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => str_repeat('a', 256),
            'author' => $book->author,
            'isbn' => $book->isbn,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['title' => 'タイトルは255文字以内で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => str_repeat('a', 256),
        ]);
    }

    public function test_author_cannot_exceed_255_characters_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'author' => '更新前著者',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => $book->title,
            'author' => str_repeat('a', 256),
            'isbn' => $book->isbn,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['author' => '著者名は255文字以内で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'author' => '更新前著者',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'author' => str_repeat('a', 256),
        ]);
    }

    public function test_image_url_cannot_exceed_255_characters_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'image_url' => 'https://example.com/original.jpg',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'image_url' => 'https://example.com/'.str_repeat('a', 240),
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['image_url' => '画像URLは255文字以内で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'image_url' => 'https://example.com/original.jpg',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'image_url' => 'https://example.com/'.str_repeat('a', 240),
        ]);
    }

    public function test_required_fields_are_rejected_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000030',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは必須です。',
            'author' => '著者名は必須です。',
            'isbn' => 'ISBNは必須です。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000030',
        ]);
    }

    public function test_invalid_isbn_is_rejected_when_updating_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create([
            'isbn' => '9784000000040',
        ]);
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => '123',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors(['isbn' => 'ISBNは13桁の数字で入力してください。']);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9784000000040',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'isbn' => '123',
        ]);
    }
}
