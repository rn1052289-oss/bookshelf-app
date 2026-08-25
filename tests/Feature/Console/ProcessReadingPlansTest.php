<?php

namespace Tests\Feature\Console;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_in_progress_plan_becomes_expired(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->subDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status
        );

        Carbon::setTestNow();
    }

    public function test_plan_due_today_does_not_become_expired(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        Carbon::setTestNow();
    }

    public function test_three_days_before_notification_is_sent(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notification = $readingPlan->user
            ->notifications()
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            'three_days_before',
            $notification->data['timing']
        );

        Carbon::setTestNow();
    }

    public function test_three_days_before_notification_is_not_sent_twice(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notificationCount = $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', 'three_days_before')
            ->count();

        $this->assertSame(1, $notificationCount);

        Carbon::setTestNow();
    }

    public function test_on_due_date_notification_is_sent(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notification = $readingPlan->user
            ->notifications()
            ->where(
                'data->timing',
                ReadingPlanReminderTiming::OnDueDate->value
            )
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            ReadingPlanReminderTiming::OnDueDate->value,
            $notification->data['timing']
        );

        Carbon::setTestNow();
    }

    public function test_on_due_date_notification_is_not_sent_twice(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notificationCount = $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where(
                'data->timing',
                ReadingPlanReminderTiming::OnDueDate->value
            )
            ->count();

        $this->assertSame(1, $notificationCount);

        Carbon::setTestNow();
    }

    public function test_three_days_after_notification_is_sent(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notification = $readingPlan->user
            ->notifications()
            ->where(
                'data->timing',
                ReadingPlanReminderTiming::ThreeDaysAfter->value
            )
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            ReadingPlanReminderTiming::ThreeDaysAfter->value,
            $notification->data['timing']
        );

        Carbon::setTestNow();
    }

    public function test_three_days_after_notification_is_not_sent_twice(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notificationCount = $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where(
                'data->timing',
                ReadingPlanReminderTiming::ThreeDaysAfter->value
            )
            ->count();

        $this->assertSame(1, $notificationCount);

        Carbon::setTestNow();
    }

    public function test_notification_is_not_sent_to_plan_outside_reminder_timing(): void
    {
        Carbon::setTestNow('2026-08-23 20:00:00');

        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $notificationCount = $readingPlan->user
            ->notifications()
            ->count();

        $this->assertSame(0, $notificationCount);

        Carbon::setTestNow();
    }
}
