<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use PHPUnit\Framework\TestCase;

class ReadingPlanTest extends TestCase
{
    public function test_fillable_attributes_are_correct(): void
    {
        $readingPlan = new ReadingPlan;

        $this->assertSame([
            'user_id',
            'book_id',
            'target_date',
            'status',
            'completed_at',
            'reminded_at',
        ], $readingPlan->getFillable());
    }

    public function test_casts_are_correct(): void
    {
        $readingPlan = new ReadingPlan;
        $casts = $readingPlan->getCasts();

        $this->assertSame('date', $casts['target_date']);
        $this->assertSame(ReadingPlanStatus::class, $casts['status']);
        $this->assertSame('datetime', $casts['completed_at']);
        $this->assertSame('datetime', $casts['reminded_at']);
    }
}
