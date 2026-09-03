<?php

namespace Tests\Unit\Enums;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    public function test_label_returns_correct_japanese_status(): void
    {
        $this->assertSame('読書中', ReadingPlanStatus::InProgress->label());
        $this->assertSame('読了済み', ReadingPlanStatus::Completed->label());
        $this->assertSame('期限切れ', ReadingPlanStatus::Expired->label());
    }

    public function test_badge_class_returns_correct_css_class(): void
    {
        $this->assertSame('bg-blue-100 text-blue-800', ReadingPlanStatus::InProgress->badgeClass());
        $this->assertSame('bg-green-100 text-green-800', ReadingPlanStatus::Completed->badgeClass());
        $this->assertSame('bg-red-100 text-red-800', ReadingPlanStatus::Expired->badgeClass());
    }

    public function test_status_values_are_correct(): void
    {
        $this->assertSame('in_progress', ReadingPlanStatus::InProgress->value);
        $this->assertSame('completed', ReadingPlanStatus::Completed->value);
        $this->assertSame('expired', ReadingPlanStatus::Expired->value);
    }
}
