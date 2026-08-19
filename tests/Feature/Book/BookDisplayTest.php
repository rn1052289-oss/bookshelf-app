<?php

namespace Tests\Feature\Book;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_books()
    {
        $response = $this->get('/books');

        $response->assertStatus(200);
    }

    public function test_can_get_books_from_root()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_books_are_paginated_by_10()
    {
        Book::factory()->count(11)->create();

        $response = $this->get('/books');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });
    }

    public function test_can_get_book_detail()
    {
        $book = Book::factory()->create();

        $response = $this->get("/books/{$book->id}");

        $response->assertStatus(200);
    }

    public function test_can_search_books_by_title()
    {
        $matchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '鈴木花子',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($matchedBook, $unmatchedBook) {
            return $books->contains($matchedBook)
                && ! $books->contains($unmatchedBook);
        });
    }

    public function test_can_search_books_by_author()
    {
        $matchedBook = Book::factory()->create([
            'title' => 'Web開発入門',
            'author' => '山田太郎',
        ]);

        $unmatchedBook = Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '鈴木花子',
        ]);

        $response = $this->get('/books?keyword=山田');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($matchedBook, $unmatchedBook) {
            return $books->contains($matchedBook)
                && ! $books->contains($unmatchedBook);
        });
    }

    public function test_can_filter_books_by_genre()
    {
        $targetGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();

        $matchedBook = Book::factory()->create();
        $unmatchedBook = Book::factory()->create();

        $matchedBook->genres()->attach($targetGenre);
        $unmatchedBook->genres()->attach($otherGenre);

        $response = $this->get("/books?genre={$targetGenre->id}");

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($matchedBook, $unmatchedBook) {
            return $books->contains($matchedBook)
                && ! $books->contains($unmatchedBook);
        });
    }

    public function test_newest_sort_orders_books_by_created_at_desc()
    {
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=newest');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($newBook, $oldBook) {
            return $books->first()->is($newBook)
                && $books->last()->is($oldBook);
        });
    }

    public function test_oldest_sort_orders_books_by_created_at_asc()
    {
        $oldBook = Book::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=oldest');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($oldBook, $newBook) {
            return $books->first()->is($oldBook)
                && $books->last()->is($newBook);
        });
    }

    public function test_title_sort_orders_books_by_title()
    {
        $secondBook = Book::factory()->create([
            'title' => 'B Book',
        ]);

        $firstBook = Book::factory()->create([
            'title' => 'A Book',
        ]);

        $response = $this->get('/books?sort=title');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($firstBook, $secondBook) {
            return $books->first()->is($firstBook)
                && $books->last()->is($secondBook);
        });
    }

    public function test_rating_sort_orders_books_by_average_rating_desc()
    {
        $highRatedBook = Book::factory()->create();
        $lowRatedBook = Book::factory()->create();
        $bookWithoutReview = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) use ($highRatedBook, $lowRatedBook, $bookWithoutReview) {
            return $books->first()->is($highRatedBook)
                && $books->get(1)->is($lowRatedBook)
                && $books->last()->is($bookWithoutReview);
        });
    }

    public function test_search_conditions_are_preserved_in_pagination_links()
    {
        Book::factory()->count(11)->create([
            'title' => 'Laravel Book',
        ]);

        $response = $this->get('/books?keyword=Laravel&sort=newest');

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            $nextPageUrl = $books->nextPageUrl();

            return $nextPageUrl !== null
                && str_contains($nextPageUrl, 'keyword=Laravel')
                && str_contains($nextPageUrl, 'sort=newest');
        });
    }
}
