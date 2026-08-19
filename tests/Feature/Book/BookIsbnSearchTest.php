<?php

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_book_by_isbn()
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍',
                            'authors' => ['山田太郎'],
                            'publishedDate' => '2026-08-19',
                            'description' => 'テスト説明',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(200);
        $response->assertJson([
            'title' => 'テスト書籍',
            'author' => '山田太郎',
            'published_date' => '2026-08-19',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
        ]);
    }

    public function test_invalid_isbn_returns_validation_error()
    {
        $response = $this->get('/books/isbn/123');

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ISBNは13桁の数字で入力してください。',
        ]);
    }

    public function test_google_books_api_failure_returns_error()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(503);
        $response->assertJson([
            'error' => 'Google Books APIから書籍情報を取得できませんでした。',
        ]);
    }
}
