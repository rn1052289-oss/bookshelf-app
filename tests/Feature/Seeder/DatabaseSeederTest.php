<?php

namespace Tests\Feature\Seeder;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_successfully(): void
    {
        $this->artisan('db:seed')->assertExitCode(0);
    }

    public function test_database_seeder_creates_expected_users_genres_and_books(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, User::count());
        $this->assertSame(10, Genre::count());
        $this->assertSame(11, Book::count());
    }

    public function test_users_genres_and_books_are_not_duplicated_when_seeded_again(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, User::count());
        $this->assertSame(10, Genre::count());
        $this->assertSame(11, Book::count());
    }

    public function test_favorites_and_review_likes_are_linked_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();

        $this->assertTrue(
            $yamada->favoriteBooks()
                ->where('isbn', '9784422100524')
                ->exists()
        );

        $reviewsWithLikes = Review::with('likedByUsers')
            ->get()
            ->filter(
                fn (Review $review): bool => $review->likedByUsers->isNotEmpty()
            );

        $this->assertGreaterThan(0, $reviewsWithLikes->count());

        $reviewsWithLikes->each(function (Review $review): void {
            $this->assertFalse(
                $review->likedByUsers->contains('id', $review->user_id)
            );
        });
    }

    public function test_reading_plan_seeder_creates_six_fixed_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, ReadingPlan::count());

        $this->assertSame(
            [1, 2, 3, 4, 5, 6],
            ReadingPlan::orderBy('id')
                ->pluck('id')
                ->all()
        );
    }
}
