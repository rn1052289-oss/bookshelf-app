<?php

namespace Tests\Feature\ReviewLike;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $response = $this
            ->actingAs($user)
            ->from("/books/{$book->id}")
            ->post("/reviews/{$review->id}/like");

        $response->assertRedirect("/books/{$book->id}");

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_can_remove_review_like(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $user->likedReviews()->attach($review->id);

        $response = $this
            ->actingAs($user)
            ->from("/books/{$book->id}")
            ->post("/reviews/{$review->id}/like");

        $response->assertRedirect("/books/{$book->id}");

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_review_like_is_not_duplicated(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseCount('review_likes', 1);
    }

    public function test_user_can_like_own_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '自分のレビュー',
        ]);

        $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_cannot_toggle_review_like(): void
    {
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テストレビュー',
        ]);

        $response = $this->post("/reviews/{$review->id}/like");

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('review_likes', 0);
    }
}
