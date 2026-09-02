<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * 読書計画の初期データ6件をID固定で登録する。
     */
    public function run(): void
    {
        $today = Carbon::today();

        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        $books = Book::orderBy('id')->take(6)->get();

        $readingPlans = [
            [
                'id' => 1,
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
                'reminded_at' => null,
            ],
            [
                'id' => 2,
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy(),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
                'reminded_at' => null,
            ],
            [
                'id' => 3,
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
                'reminded_at' => null,
            ],
            [
                'id' => 4,
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
                'reminded_at' => null,
            ],
            [
                'id' => 5,
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy()->subDays(10),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDays(5),
                'reminded_at' => null,
            ],
            [
                'id' => 6,
                'user_id' => $suzuki->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->addDays(5),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
                'reminded_at' => null,
            ],
        ];

        collect($readingPlans)->each(function (array $readingPlanData): void {
            $readingPlan = ReadingPlan::findOrNew($readingPlanData['id']);

            $readingPlan->id = $readingPlanData['id'];
            $readingPlan->user_id = $readingPlanData['user_id'];
            $readingPlan->book_id = $readingPlanData['book_id'];
            $readingPlan->target_date = $readingPlanData['target_date'];
            $readingPlan->status = $readingPlanData['status'];
            $readingPlan->completed_at = $readingPlanData['completed_at'];
            $readingPlan->reminded_at = $readingPlanData['reminded_at'];

            $readingPlan->save();
        });
    }
}
