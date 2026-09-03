<?php

namespace Tests\Feature\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_registered_books(): void
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $book = $user->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $this->assertTrue($user->books->contains($book));
    }

    public function test_book_can_get_owner(): void
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $this->assertTrue($book->user->is($user));
    }

    public function test_book_can_get_genres(): void
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $genre = Genre::create(['name' => '小説']);

        $book->genres()->attach($genre);

        $this->assertTrue($book->genres->contains($genre));
    }

    public function test_book_can_get_reviews(): void
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $this->assertTrue($book->reviews->contains($review));
    }

    public function test_user_can_get_favorite_books(): void
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $book = Book::create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $user->favoriteBooks()->attach($book);

        $this->assertTrue($user->favoriteBooks->contains($book));
    }

    public function test_review_can_get_users_who_liked_it(): void
    {
        $reviewer = User::create([
            'name' => 'レビュー投稿者',
            'email' => 'reviewer@example.com',
            'password' => 'password',
        ]);

        $likingUser = User::create([
            'name' => 'いいねユーザー',
            'email' => 'liker@example.com',
            'password' => 'password',
        ]);

        $book = Book::create([
            'user_id' => $reviewer->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
        ]);

        $review = Review::create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $review->likedByUsers()->attach($likingUser);

        $this->assertTrue($review->likedByUsers->contains($likingUser));
    }

    public function test_reading_plan_can_get_user_and_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->for($user)->for($book)->create();

        $this->assertTrue($readingPlan->user->is($user));
        $this->assertTrue($readingPlan->book->is($book));
    }

    public function test_reading_plan_status_is_cast_to_enum(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->assertInstanceOf(ReadingPlanStatus::class, $readingPlan->status);
        $this->assertSame(ReadingPlanStatus::Completed, $readingPlan->status);
    }

    public function test_book_can_get_reading_plans(): void
    {
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $this->assertTrue($book->readingPlans->contains($readingPlan));
    }
}
