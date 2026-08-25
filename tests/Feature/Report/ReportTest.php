<?php

namespace Tests\Feature\Report;

use App\Models\Book;
use App\Models\Genre;
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

    public function test_genre_ratings_top_five_is_correct(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $genreA = Genre::factory()->create(['name' => 'ジャンルA']);
        $genreB = Genre::factory()->create(['name' => 'ジャンルB']);
        $genreC = Genre::factory()->create(['name' => 'ジャンルC']);
        $genreD = Genre::factory()->create(['name' => 'ジャンルD']);
        $genreE = Genre::factory()->create(['name' => 'ジャンルE']);
        $genreF = Genre::factory()->create(['name' => 'ジャンルF']);

        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();
        $bookC = Book::factory()->create();
        $bookD = Book::factory()->create();
        $bookE = Book::factory()->create();
        $bookF = Book::factory()->create();

        $bookA->genres()->attach([$genreA->id, $genreB->id]);
        $bookB->genres()->attach($genreB->id);
        $bookC->genres()->attach($genreC->id);
        $bookD->genres()->attach($genreD->id);
        $bookE->genres()->attach($genreE->id);
        $bookF->genres()->attach($genreF->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookB->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookC->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookD->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookE->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookF->id,
            'rating' => 1,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $bookA->id,
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertStatus(200);

        $response->assertViewHas('stats', function (array $stats): bool {
            $genreRatings = $stats['genre_ratings'];

            return $genreRatings->count() === 5
                && $genreRatings->pluck('name')->all() === [
                    'ジャンルA',
                    'ジャンルB',
                    'ジャンルC',
                    'ジャンルD',
                    'ジャンルE',
                ]
                && $genreRatings[0]['average_rating'] === 5.0
                && $genreRatings[0]['count'] === 1
                && $genreRatings[1]['average_rating'] === 4.0
                && $genreRatings[1]['count'] === 2;
        });
    }

    public function test_top_rated_books_top_five_is_correct(): void
    {
        $user = User::factory()->create();

        $bookA = Book::factory()->create([
            'title' => '書籍A',
            'created_at' => now()->subDays(6),
        ]);
        $bookB = Book::factory()->create([
            'title' => '書籍B',
            'created_at' => now()->subDays(5),
        ]);
        $bookC = Book::factory()->create([
            'title' => '書籍C',
            'created_at' => now()->subDays(4),
        ]);
        $bookD = Book::factory()->create([
            'title' => '書籍D',
            'created_at' => now()->subDays(3),
        ]);
        $bookE = Book::factory()->create([
            'title' => '書籍E',
            'created_at' => now()->subDays(2),
        ]);
        $bookF = Book::factory()->create([
            'title' => '書籍F',
            'created_at' => now()->subDay(),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookB->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookB->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookC->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookD->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookE->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $bookF->id,
            'rating' => 4,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);

        $response->assertViewHas('stats', function (array $stats): bool {
            $topRatedBooks = $stats['top_rated_books'];

            return $topRatedBooks->count() === 5
                && $topRatedBooks->pluck('title')->all() === [
                    '書籍A',
                    '書籍B',
                    '書籍F',
                    '書籍E',
                    '書籍D',
                ]
                && $topRatedBooks[0]['average_rating'] === 5.0
                && $topRatedBooks[1]['average_rating'] === 4.5
                && $topRatedBooks[1]['review_count'] === 2
                && $topRatedBooks[1]['rating'] === 5;
        });
    }

    public function test_top_rated_books_excludes_books_below_four(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'title' => '高評価書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価書籍',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $highRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response->assertStatus(200);

        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['top_rated_books']->pluck('title')->all() === [
                '高評価書籍',
            ];
        });
    }
}
