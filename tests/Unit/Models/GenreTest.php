<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use PHPUnit\Framework\TestCase;

class GenreTest extends TestCase
{
    public function test_fillable_attributes_are_correct(): void
    {
        $genre = new Genre;

        $this->assertSame([
            'name',
        ], $genre->getFillable());
    }
}
