<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
