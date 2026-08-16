<?php

namespace Tests\Feature\Book;

use App\Models\Book;
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
}
