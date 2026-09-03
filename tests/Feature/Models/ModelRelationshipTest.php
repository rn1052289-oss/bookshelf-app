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
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->assertTrue($user->books->contains($book));
    }

    public function test_book_can_get_owner(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->assertTrue($book->user->is($user));
    }

    public function test_book_can_get_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        $this->assertTrue($book->genres->contains($genre));
    }

    public function test_book_can_get_reviews(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->for($book)->create();

        $this->assertTrue($book->reviews->contains($review));
    }

    public function test_user_can_get_favorite_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book);

        $this->assertTrue($user->favoriteBooks->contains($book));
    }

    public function test_review_can_get_users_who_liked_it(): void
    {
        $review = Review::factory()->create();
        $likingUser = User::factory()->create();

        $review->likedByUsers()->attach($likingUser);

        $this->assertTrue($review->likedByUsers->contains($likingUser));
    }

    public function test_reading_plan_can_get_user_and_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create();

        $this->assertTrue($readingPlan->user->is($user));
        $this->assertTrue($readingPlan->book->is($book));
    }

    public function test_reading_plan_status_is_cast_to_enum(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->assertInstanceOf(
            ReadingPlanStatus::class,
            $readingPlan->status
        );
        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );
    }

    public function test_book_can_get_reading_plans(): void
    {
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($book)
            ->create();

        $this->assertTrue($book->readingPlans->contains($readingPlan));
    }
}
