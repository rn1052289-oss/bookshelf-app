<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * 各レビューへ初期いいねを登録する。
     */
    public function run(): void
    {
        $users = User::all();

        Review::orderBy('id')
            ->get()
            ->each(function (Review $review, int $index) use ($users): void {
                $likeCount = $index % 4;

                $likerIds = $users
                    ->reject(fn (User $user): bool => $user->id === $review->user_id)
                    ->take($likeCount)
                    ->pluck('id')
                    ->all();

                $review->likedByUsers()->syncWithoutDetaching($likerIds);
            });
    }
}
