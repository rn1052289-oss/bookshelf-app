<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdateReadingPlanRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 読書計画更新時のバリデーションルールを返す。
     */
    public function rules(): array
    {
        return [
            'target_date' => ['required', 'date'],
        ];
    }

    /**
     * 基本バリデーション後の追加チェックを行う。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('target_date')) {
                return;
            }

            $plan = $this->route('plan');

            if (! $plan instanceof ReadingPlan) {
                return;
            }

            if ($this->user()->id !== $plan->user_id) {
                return;
            }

            if ($plan->status !== ReadingPlanStatus::Expired) {
                return;
            }

            $targetDate = Carbon::parse($this->input('target_date'));

            if ($targetDate->lt(Carbon::today())) {
                return;
            }

            $hasInProgressPlan = ReadingPlan::query()
                ->where('user_id', $plan->user_id)
                ->where('book_id', $plan->book_id)
                ->where('status', ReadingPlanStatus::InProgress->value)
                ->where('id', '!=', $plan->id)
                ->exists();

            if ($hasInProgressPlan) {
                $validator->errors()->add(
                    'target_date',
                    'この書籍には、すでに進行中の読書計画があります。'
                );
            }
        });
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     */
    public function messages(): array
    {
        return [
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は正しい日付で入力してください。',
        ];
    }
}
