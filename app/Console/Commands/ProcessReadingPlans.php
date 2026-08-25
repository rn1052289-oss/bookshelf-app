<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限状態とリマインダー通知を日次処理する';

    /**
     * Console Commandを実行する。
     */
    public function handle(): int
    {
        $result = DB::transaction(function (): array {
            return [
                'expired' => $this->expireOverduePlans(),
                'three_days_before' => $this->sendThreeDaysBeforeNotifications(),
                'on_due_date' => $this->sendOnDueDateNotifications(),
                'three_days_after' => $this->sendThreeDaysAfterNotifications(),
            ];
        });

        $this->info("{$result['expired']}件の読書計画を期限切れに変更しました。");
        $this->info("{$result['three_days_before']}件の3日前通知を送信しました。");
        $this->info("{$result['on_due_date']}件の当日通知を送信しました。");
        $this->info("{$result['three_days_after']}件の期限超過3日後通知を送信しました。");

        return self::SUCCESS;
    }

    /**
     * 期限を過ぎた読書中の計画を期限切れへ変更する。
     */
    private function expireOverduePlans(): int
    {
        return ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', '<', Carbon::today())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);
    }

    /**
     * 期日の3日前になった読書中の計画へ通知する。
     */
    private function sendThreeDaysBeforeNotifications(): int
    {
        $timing = ReadingPlanReminderTiming::ThreeDaysBefore;

        $readingPlans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', Carbon::today()->addDays(3))
            ->get();

        $notificationCount = 0;

        foreach ($readingPlans as $readingPlan) {
            if ($this->hasNotificationBeenSent($readingPlan, $timing)) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanReminderNotification($readingPlan, $timing)
            );

            $notificationCount++;
        }

        return $notificationCount;
    }

    /**
     * 期日当日の読書中の計画へ通知する。
     */
    private function sendOnDueDateNotifications(): int
    {
        $timing = ReadingPlanReminderTiming::OnDueDate;

        $readingPlans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('target_date', Carbon::today())
            ->get();

        $notificationCount = 0;

        foreach ($readingPlans as $readingPlan) {
            if ($this->hasNotificationBeenSent($readingPlan, $timing)) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanReminderNotification($readingPlan, $timing)
            );

            $notificationCount++;
        }

        return $notificationCount;
    }

    /**
     * 期日から3日経過した期限切れの計画へ通知する。
     */
    private function sendThreeDaysAfterNotifications(): int
    {
        $timing = ReadingPlanReminderTiming::ThreeDaysAfter;

        $readingPlans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired->value)
            ->whereDate('target_date', Carbon::today()->subDays(3))
            ->get();

        $notificationCount = 0;

        foreach ($readingPlans as $readingPlan) {
            if ($this->hasNotificationBeenSent($readingPlan, $timing)) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanReminderNotification($readingPlan, $timing)
            );

            $notificationCount++;
        }

        return $notificationCount;
    }

    /**
     * 同じ読書計画・通知タイミングの通知が送信済みか確認する。
     */
    private function hasNotificationBeenSent(ReadingPlan $readingPlan, ReadingPlanReminderTiming $timing): bool
    {
        return $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing->value)
            ->exists();
    }
}
