<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reading_plans()
    {
        $response = $this->get('/reading-plans');

        $response->assertRedirect('/login');
    }

    public function test_user_can_only_see_own_reading_plans()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownBook = Book::factory()->create([
            'title' => '自分の読書計画の本',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他人の読書計画の本',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $ownBook->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
        ]);

        $response = $this->actingAs($user)->get('/reading-plans');

        $response->assertStatus(200);
        $response->assertSee('自分の読書計画の本');
        $response->assertDontSee('他人の読書計画の本');
    }

    public function test_reading_plans_can_be_filtered_by_status()
    {
        $user = User::factory()->create();

        $inProgressBook = Book::factory()->create([
            'title' => '読書中の本',
        ]);

        $completedBook = Book::factory()->create([
            'title' => '読了済みの本',
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $completedBook->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/reading-plans?status=completed');

        $response->assertStatus(200);
        $response->assertSee('読了済みの本');
        $response->assertDontSee('読書中の本');
    }

    public function test_authenticated_user_can_create_reading_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_reading_plan_validation_errors_are_displayed_in_japanese()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => '',
            'target_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors([
            'book_id',
            'target_date',
        ]);

        $this->assertEquals(
            '書籍を選択してください。',
            session('errors')->first('book_id')
        );

        $this->assertEquals(
            '期日は今日以降の日付を入力してください。',
            session('errors')->first('target_date')
        );
    }

    public function test_owner_can_update_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);
        $newTargetDate = now()->addMonth()->toDateString();

        $response = $this->actingAs($user)
            ->put("/reading-plans/{$plan->id}", [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_other_user_cannot_update_reading_plan()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->put("/reading-plans/{$plan->id}", [
                'target_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_reading_plan()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete("/reading-plans/{$plan->id}");

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_other_user_cannot_delete_reading_plan()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/reading-plans/{$plan->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_owner_can_complete_reading_plan()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post("/reading-plans/{$plan->id}/complete");

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_completed_at_is_set_when_reading_plan_is_completed()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $this->actingAs($user)
            ->post("/reading-plans/{$plan->id}/complete");

        $plan->refresh();

        $this->assertNotNull($plan->completed_at);
    }

    public function test_other_user_cannot_complete_reading_plan()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($otherUser)
            ->post("/reading-plans/{$plan->id}/complete");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_expired_plan_returns_to_in_progress_when_target_date_is_changed_to_today_or_future()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $newTargetDate = now()->toDateString();

        $response = $this->actingAs($user)
            ->put("/reading-plans/{$plan->id}", [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_completed_plan_remains_completed_when_target_date_is_changed()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $newTargetDate = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)
            ->put("/reading-plans/{$plan->id}", [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }
}
