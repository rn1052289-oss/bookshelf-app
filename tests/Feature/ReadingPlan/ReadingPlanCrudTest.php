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

    public function test_guest_cannot_access_reading_plan_routes()
    {
        $plan = ReadingPlan::factory()->create();

        $this->get('/reading-plans')->assertRedirect('/login');
        $this->get('/reading-plans/create')->assertRedirect('/login');

        $this->post('/reading-plans', [
            'book_id' => $plan->book_id,
            'target_date' => now()->addWeek()->toDateString(),
        ])->assertRedirect('/login');

        $this->get("/reading-plans/{$plan->id}/edit")->assertRedirect('/login');

        $this->put("/reading-plans/{$plan->id}", [
            'target_date' => now()->addMonth()->toDateString(),
        ])->assertRedirect('/login');

        $this->delete("/reading-plans/{$plan->id}")->assertRedirect('/login');
        $this->post("/reading-plans/{$plan->id}/complete")->assertRedirect('/login');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
        ]);
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
        $response->assertViewIs('reading-plans.index');
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

        $response = $this->actingAs($user)->get('/reading-plans?status=completed');

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.index');
        $response->assertViewHas('currentStatus', 'completed');
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
        $response->assertSessionHas('success', '読書計画を登録しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_user_cannot_create_duplicate_in_progress_plan_for_same_book()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertSessionHasErrors([
            'book_id' => 'この書籍には、すでに進行中の読書計画があります。',
        ]);

        $this->assertSame(
            1,
            ReadingPlan::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->where('status', ReadingPlanStatus::InProgress->value)
                ->count()
        );
    }

    public function test_user_can_create_new_plan_after_completed_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $targetDate = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を登録しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_user_can_create_new_plan_after_expired_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subDay()->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $targetDate = now()->addWeek()->toDateString();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を登録しました。');

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
            'book_id' => '書籍を選択してください。',
            'target_date' => '期日は今日以降の日付を入力してください。',
        ]);
    }

    public function test_owner_can_update_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);
        $newTargetDate = now()->addMonth()->toDateString();

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を更新しました。');

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

        $response = $this->actingAs($otherUser)->put("/reading-plans/{$plan->id}", [
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

        $response = $this->actingAs($user)->delete("/reading-plans/{$plan->id}");

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を削除しました。');

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

        $response = $this->actingAs($otherUser)->delete("/reading-plans/{$plan->id}");

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

        $response = $this->actingAs($user)->post("/reading-plans/{$plan->id}/complete");

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を読了しました。');

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

        $this->actingAs($user)->post("/reading-plans/{$plan->id}/complete");

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

        $response = $this->actingAs($otherUser)->post("/reading-plans/{$plan->id}/complete");

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

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_expired_plan_cannot_return_to_in_progress_when_another_in_progress_plan_exists()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $expiredTargetDate = now()->subDay()->toDateString();

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $expiredTargetDate,
            'status' => ReadingPlanStatus::Expired,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->put("/reading-plans/{$expiredPlan->id}", [
            'target_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHasErrors([
            'target_date' => 'この書籍には、すでに進行中の読書計画があります。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'target_date' => $expiredTargetDate,
            'status' => ReadingPlanStatus::Expired->value,
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

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    public function test_authenticated_user_can_access_reading_plan_create_page()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get('/reading-plans/create');

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.create');
        $response->assertViewHas('books', function ($books) use ($book) {
            return $books->contains('id', $book->id);
        });
    }

    public function test_owner_can_access_reading_plan_edit_page()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/reading-plans/{$plan->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.edit');
        $response->assertViewHas('readingPlan', function ($readingPlan) use ($plan) {
            return $readingPlan->is($plan);
        });
    }

    public function test_other_user_cannot_access_reading_plan_edit_page()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->get("/reading-plans/{$plan->id}/edit");

        $response->assertStatus(403);
    }

    public function test_nonexistent_reading_plan_returns_404_on_edit_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reading-plans/999999/edit');

        $response->assertStatus(404);
    }

    public function test_nonexistent_reading_plan_returns_404_when_updating()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/reading-plans/999999', [
            'target_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertStatus(404);
    }

    public function test_nonexistent_reading_plan_returns_404_when_deleting()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/reading-plans/999999');

        $response->assertStatus(404);
    }

    public function test_nonexistent_reading_plan_returns_404_when_completing()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans/999999/complete');

        $response->assertStatus(404);
    }

    public function test_nonexistent_book_is_rejected_when_creating_reading_plan()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => 999999,
            'target_date' => now()->addWeek()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['book_id' => '選択された書籍が存在しません。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_target_date_is_required_when_creating_reading_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は必須です。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_invalid_target_date_is_rejected_when_creating_reading_plan()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は正しい日付で入力してください。']);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_target_date_is_required_when_updating_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);
        $originalTargetDate = $plan->target_date->toDateString();

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は必須です。']);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalTargetDate,
        ]);
    }

    public function test_invalid_target_date_is_rejected_when_updating_reading_plan()
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);
        $originalTargetDate = $plan->target_date->toDateString();

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は正しい日付で入力してください。']);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $originalTargetDate,
        ]);
    }

    public function test_expired_plan_remains_expired_when_target_date_is_changed_to_past_date()
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subWeek()->toDateString(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $newTargetDate = now()->subDay()->toDateString();

        $response = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }
}
