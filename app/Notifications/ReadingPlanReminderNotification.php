<?php

namespace App\Notifications;

use App\Enums\ReadingPlanReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * 読書計画リマインダー通知を作成する。
     */
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly ReadingPlanReminderTiming $timing
    ) {}

    /**
     * 通知の送信チャンネルを取得する。
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database Channelへ保存する通知データを取得する。
     *
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing->value,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    /**
     * 通知タイトルを取得する。
     */
    private function title(): string
    {
        return match ($this->timing) {
            ReadingPlanReminderTiming::ThreeDaysBefore => '読書期限のお知らせ',
            ReadingPlanReminderTiming::OnDueDate => '読書期限当日のお知らせ',
            ReadingPlanReminderTiming::ThreeDaysAfter => '読書期限超過のお知らせ',
        };
    }

    /**
     * 通知本文を取得する。
     */
    private function body(): string
    {
        $bookTitle = $this->readingPlan->book->title;

        return match ($this->timing) {
            ReadingPlanReminderTiming::ThreeDaysBefore => "『{$bookTitle}』の読書期限まであと3日です。",

            ReadingPlanReminderTiming::OnDueDate => "『{$bookTitle}』の読書期限は今日です。",

            ReadingPlanReminderTiming::ThreeDaysAfter => "『{$bookTitle}』の読書期限から3日経過しました。読書を再開してみませんか？",
        };
    }
}
