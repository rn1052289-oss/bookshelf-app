<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_ranking()
    {
        $response = $this->get('/ranking');

        $response->assertStatus(200);
    }

    public function test_ranking_displays_top_10_books()
    {
        $books = Book::factory()->count(11)->create();

        $books->each(function ($book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        });

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }

    public function test_books_without_reviews_are_not_in_ranking()
    {
        $bookWithReview = Book::factory()->create();
        $bookWithoutReview = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $bookWithReview->id,
            'rating' => 5,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($bookWithReview, $bookWithoutReview) {
            return $rankedBooks->contains($bookWithReview)
                && ! $rankedBooks->contains($bookWithoutReview);
        });
    }

    public function test_ranking_is_ordered_by_average_rating()
    {
        $highRatedBook = Book::factory()->create();
        $lowRatedBook = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->get('/ranking');

        $response->assertStatus(200);
        $response->assertViewHas('rankedBooks', function ($rankedBooks) use ($highRatedBook, $lowRatedBook) {
            return $rankedBooks->first()->is($highRatedBook)
                && $rankedBooks->last()->is($lowRatedBook);
        });
    }
}
