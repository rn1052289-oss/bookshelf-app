<?php

namespace Tests\Unit\Enums;

use App\Enums\ReadingPlanReminderTiming;
use PHPUnit\Framework\TestCase;

class ReadingPlanReminderTimingTest extends TestCase
{
    public function test_timing_values_are_correct(): void
    {
        $this->assertSame('three_days_before', ReadingPlanReminderTiming::ThreeDaysBefore->value);
        $this->assertSame('on_due_date', ReadingPlanReminderTiming::OnDueDate->value);
        $this->assertSame('three_days_after', ReadingPlanReminderTiming::ThreeDaysAfter->value);
    }
}
