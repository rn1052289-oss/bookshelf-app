<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_books()
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    public function test_can_get_book_detail_with_genres_and_reviews()
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $book = Book::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.genres.0.name', '小説');
        $response->assertJsonPath('data.reviews.0.user_name', 'テストユーザー');
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', 'とても面白かったです。');
    }

    public function test_returns_404_when_book_does_not_exist()
    {
        $response = $this->getJson('/api/v1/books/9999');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_can_search_books_by_keyword()
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '銀河鉄道の夜',
            'author' => '宮沢賢治',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=夏目漱石');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'title' => '吾輩は猫である',
        ]);
        $response->assertJsonFragment([
            'title' => '坊っちゃん',
        ]);
        $response->assertJsonMissing([
            'title' => '銀河鉄道の夜',
        ]);
    }

    public function test_can_filter_books_by_genre()
    {
        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $business = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '小説の本',
        ]);

        $businessBook = Book::factory()->create([
            'title' => 'ビジネスの本',
        ]);

        $novelBook->genres()->attach($novel->id);
        $businessBook->genres()->attach($business->id);

        $response = $this->getJson("/api/v1/books?genre={$novel->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'title' => '小説の本',
        ]);
        $response->assertJsonMissing([
            'title' => 'ビジネスの本',
        ]);
    }

    public function test_books_are_paginated()
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
    }

    public function test_can_create_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API登録テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000010',
            'published_date' => '2026-08-20',
            'description' => 'API登録テスト用です。',
            'image_url' => 'https://example.com/api-book.jpg',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'API登録テスト');
        $response->assertJsonPath('data.author', 'テスト著者');
        $response->assertJsonPath('data.isbn', '9784000000010');
        $response->assertJsonPath('data.genres.0.id', $genre->id);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API登録テスト',
            'isbn' => '9784000000010',
        ]);
    }

    public function test_create_book_validation_errors_are_returned_in_japanese()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'genre_ids' => [],
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'genre_ids',
        ]);

        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは必須です。'
        );

        $response->assertJsonPath(
            'errors.author.0',
            '著者名は必須です。'
        );

        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは必須です。'
        );

        $response->assertJsonPath(
            'errors.genre_ids.0',
            'ジャンルを1件以上選択してください。'
        );
    }

    public function test_can_update_book_with_same_isbn()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $oldGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9784000000020',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000020',
            'published_date' => '2026-08-20',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated-book.jpg',
            'genre_ids' => [$newGenre->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', '更新後タイトル');
        $response->assertJsonPath('data.isbn', '9784000000020');
        $response->assertJsonPath('data.genres.0.id', $newGenre->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'isbn' => '9784000000020',
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

    public function test_can_delete_book_with_related_data()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);
        $book->favoritedByUsers()->attach($user->id);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $review->likedByUsers()->attach($user->id);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);

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
    }

    public function test_book_resource_format()
    {
        $book = Book::factory()->create([
            'title' => 'Resourceテスト書籍',
        ]);

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                    'genres',
                    'average_rating',
                    'reviews_count',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $response->assertJsonPath('data.0.title', 'Resourceテスト書籍');
        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.genres.0.name', '小説');
        $response->assertJsonPath('data.0.average_rating', 4.5);
        $response->assertJsonPath('data.0.reviews_count', 2);
    }

    public function test_can_update_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000030',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9784000000031',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', '更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'isbn' => '9784000000031',
        ]);
    }

    public function test_guest_cannot_create_book()
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => '未認証登録テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000040',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'message',
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9784000000040',
        ]);
    }

    public function test_guest_cannot_update_book()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000050',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '未認証更新テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000050',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'message',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_guest_cannot_delete_book()
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'message',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_other_user_cannot_update_book()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
            'isbn' => '9784000000060',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '他ユーザー更新テスト',
            'author' => 'テスト著者',
            'isbn' => '9784000000060',
            'genre_ids' => [$genre->id],
        ]);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'message',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_other_user_cannot_delete_book()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'message',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
