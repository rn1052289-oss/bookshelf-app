<?php

namespace App\Models;

use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    protected $casts = [
        'published_date' => 'date',
    ];

    /**
     * 書籍を登録したユーザーを取得する。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 書籍に紐付くジャンルを取得する。
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * 書籍に投稿されたレビューを取得する。
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * 書籍をお気に入り登録したユーザーを取得する。
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * 書籍に紐付く読書計画を取得する。
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * 書籍に紐付く読書計画のリマインダー通知を削除する。
     */
    public function deleteReadingPlanReminderNotifications(): void
    {
        $this->readingPlans()
            ->with('user')
            ->get()
            ->each(function (ReadingPlan $readingPlan): void {
                $readingPlan->user
                    ->notifications()
                    ->where('type', ReadingPlanReminderNotification::class)
                    ->where('data->reading_plan_id', $readingPlan->id)
                    ->delete();
            });
    }
}
