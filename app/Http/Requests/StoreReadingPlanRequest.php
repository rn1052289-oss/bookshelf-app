<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 読書計画登録時のバリデーションルールを返す。
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '書籍を正しく選択してください。',
            'book_id.exists' => '選択された書籍が存在しません。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は正しい日付で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を入力してください。',
        ];
    }
}
