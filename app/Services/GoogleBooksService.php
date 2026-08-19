<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * ISBNからGoogle Books APIで書籍を検索する。
     */
    public function searchByIsbn(string $isbn): ?array
    {
        $response = Http::get(
            config('services.google_books.base_url').'/volumes',
            [
                'q' => "isbn:{$isbn}",
                'key' => config('services.google_books.api_key'),
                'maxResults' => 1,
            ]
        );

        $response->throw();

        return $response->json('items.0');
    }
}
