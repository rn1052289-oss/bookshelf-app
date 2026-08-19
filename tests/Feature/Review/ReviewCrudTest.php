<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_review()
    {
        $book = Book::factory()->create();

        $response = $this->post("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_authenticated_user_can_create_review()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);
    }

    public function test_invalid_rating_is_rejected()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 6,
            'comment' => 'レビューコメントです。',
        ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_author_can_update_review()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);

        $response = $this->actingAs($user)->put("/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => '更新後のコメントです。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のコメントです。',
        ]);
    }

    public function test_other_user_cannot_update_review()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);

        $response = $this->actingAs($otherUser)->put("/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => '他ユーザーによる更新です。',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);
    }

    public function test_other_user_cannot_delete_review()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除前のコメントです。',
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/reviews/{$review->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除前のコメントです。',
        ]);
    }

    public function test_author_can_delete_review()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除するレビューです。',
        ]);

        $response = $this->actingAs($user)
            ->delete("/reviews/{$review->id}");

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_review_likes_are_deleted_when_review_is_deleted()
    {
        $author = User::factory()->create();
        $likedUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'いいね付きレビューです。',
        ]);

        $review->likedByUsers()->attach($likedUser->id);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);

        $this->actingAs($author)
            ->delete("/reviews/{$review->id}");

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_other_user_cannot_access_review_edit_page()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $author->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '編集前のコメントです。',
        ]);

        $response = $this->actingAs($otherUser)
            ->get("/reviews/{$review->id}/edit");

        $response->assertStatus(403);
    }

    public function test_comment_is_required()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは必須です。',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_comment_cannot_exceed_1000_characters()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post("/books/{$book->id}/reviews", [
            'rating' => 5,
            'comment' => str_repeat('あ', 1001),
        ]);

        $response->assertSessionHasErrors([
            'comment' => 'コメントは1000文字以内で入力してください。',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }
}
