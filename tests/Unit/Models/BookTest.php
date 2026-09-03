<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function test_fillable_attributes_are_correct(): void
    {
        $book = new Book;

        $this->assertSame([
            'user_id',
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
        ], $book->getFillable());
    }

    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book;

        $this->assertSame('date', $book->getCasts()['published_date']);
    }
}
