<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use PHPUnit\Framework\TestCase;

class ReviewTest extends TestCase
{
    public function test_fillable_attributes_are_correct(): void
    {
        $review = new Review;

        $this->assertSame([
            'user_id',
            'book_id',
            'rating',
            'comment',
        ], $review->getFillable());
    }

    public function test_rating_is_cast_to_integer(): void
    {
        $review = new Review;

        $this->assertSame('integer', $review->getCasts()['rating']);
    }
}
