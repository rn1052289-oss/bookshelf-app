<?php

namespace Tests\Feature\Seeder;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BookSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\UserSeeder;
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
        $seeders = [
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
        ];

        $this->seed($seeders);
        $this->seed($seeders);

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

    public function test_reading_plan_seeder_creates_expected_scenarios(): void
    {
        $this->seed(DatabaseSeeder::class);

        $today = Carbon::today();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(6)->get();

        $plans = ReadingPlan::orderBy('id')->get()->keyBy('id');

        $this->assertSame($yamada->id, $plans[1]->user_id);
        $this->assertSame($books[0]->id, $plans[1]->book_id);
        $this->assertTrue($plans[1]->target_date->isSameDay($today->copy()->addDays(3)));
        $this->assertSame(ReadingPlanStatus::InProgress, $plans[1]->status);
        $this->assertNull($plans[1]->completed_at);

        $this->assertSame($yamada->id, $plans[2]->user_id);
        $this->assertSame($books[1]->id, $plans[2]->book_id);
        $this->assertTrue($plans[2]->target_date->isSameDay($today));
        $this->assertSame(ReadingPlanStatus::InProgress, $plans[2]->status);
        $this->assertNull($plans[2]->completed_at);

        $this->assertSame($yamada->id, $plans[3]->user_id);
        $this->assertSame($books[2]->id, $plans[3]->book_id);
        $this->assertTrue($plans[3]->target_date->isSameDay($today->copy()->subDays(3)));
        $this->assertSame(ReadingPlanStatus::InProgress, $plans[3]->status);
        $this->assertNull($plans[3]->completed_at);

        $this->assertSame($yamada->id, $plans[4]->user_id);
        $this->assertSame($books[3]->id, $plans[4]->book_id);
        $this->assertTrue($plans[4]->target_date->isSameDay($today->copy()->addDays(7)));
        $this->assertSame(ReadingPlanStatus::InProgress, $plans[4]->status);
        $this->assertNull($plans[4]->completed_at);

        $this->assertSame($yamada->id, $plans[5]->user_id);
        $this->assertSame($books[4]->id, $plans[5]->book_id);
        $this->assertTrue($plans[5]->target_date->isSameDay($today->copy()->subDays(10)));
        $this->assertSame(ReadingPlanStatus::Completed, $plans[5]->status);
        $this->assertTrue($plans[5]->completed_at->isSameDay($today->copy()->subDays(5)));

        $this->assertSame($suzuki->id, $plans[6]->user_id);
        $this->assertSame($books[5]->id, $plans[6]->book_id);
        $this->assertTrue($plans[6]->target_date->isSameDay($today->copy()->addDays(5)));
        $this->assertSame(ReadingPlanStatus::InProgress, $plans[6]->status);
        $this->assertNull($plans[6]->completed_at);
    }
}
