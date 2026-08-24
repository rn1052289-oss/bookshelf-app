<?php

namespace Tests\Feature\Report;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_report(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }

    public function test_total_reviews_is_correct(): void
    {
        $user = User::factory()->create();

        Review::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['total_reviews'] === 3;
        });
    }

    public function test_books_read_counts_unique_books(): void
    {
        $user = User::factory()->create();

        $firstBook = Book::factory()->create();
        $secondBook = Book::factory()->create();

        Review::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['books_read'] === 2;
        });
    }

    public function test_average_rating_is_correct(): void
    {
        $user = User::factory()->create();

        foreach ([5, 4, 3] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['average_rating'] === 4.0;
        });
    }

    public function test_rating_distribution_contains_all_ratings(): void
    {
        $user = User::factory()->create();

        foreach ([1, 3, 3, 5] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['rating_distribution']->all() === [
                1,
                0,
                2,
                0,
                1,
            ];
        });
    }

    public function test_other_users_reviews_are_not_included(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        Review::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['summary']['total_reviews'] === 2;
        });
    }
}
