<?php

namespace Tests\Feature\Notification;

use App\Enums\ReadingPlanReminderTiming;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_get_notifications(): void
    {
        $response = $this->get('/notifications');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_get_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read");

        $response->assertRedirect('/notifications');

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $otherUser->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $otherUser->notifications()->first();

        $response = $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read");

        $response->assertStatus(403);

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }

    public function test_unread_notification_count_decreases_after_marking_as_read(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $user->notifications()->first();

        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read");

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_guest_cannot_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $user->notifications()->first();

        $response = $this->post("/notifications/{$notification->id}/read");

        $response->assertRedirect('/login');

        $notification->refresh();

        $this->assertNull($notification->read_at);
    }
}
