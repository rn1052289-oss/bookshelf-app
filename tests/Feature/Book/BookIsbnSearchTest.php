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

    public function test_missing_google_books_fields_are_returned_as_null()
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(200);
        $response->assertJson([
            'title' => null,
            'author' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);
    }

    public function test_year_only_published_date_is_returned_as_null()
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'publishedDate' => '2026',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(200);
        $response->assertJson(['published_date' => null]);
    }

    public function test_year_month_published_date_is_returned_as_null()
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'publishedDate' => '2026-08',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(200);
        $response->assertJson(['published_date' => null]);
    }

    public function test_invalid_isbn_returns_validation_error()
    {
        $response = $this->get('/books/isbn/123');

        $response->assertStatus(422);
        $response->assertJson(['error' => 'ISBNは13桁の数字で入力してください。']);
    }

    public function test_google_books_api_failure_returns_error()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(500);
        $response->assertJson(['error' => 'API 通信エラーが発生しました。']);
    }

    public function test_returns_404_when_google_books_has_no_results()
    {
        Http::fake(['*' => Http::response(['items' => []], 200)]);

        $response = $this->get('/books/isbn/9784000000000');

        $response->assertStatus(404);
        $response->assertJson(['error' => '書籍情報が見つかりませんでした。']);
    }
}
