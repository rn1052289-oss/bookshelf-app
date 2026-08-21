<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 書籍一覧検索時のバリデーションルールを返す。
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string'],
            'genre' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     */
    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'genre.integer' => 'ジャンルIDは整数で入力してください。',
            'page.integer' => 'ページ番号は整数で入力してください。',
            'page.min' => 'ページ番号は1以上で入力してください。',
            'per_page.integer' => '1ページあたりの表示件数は整数で入力してください。',
            'per_page.min' => '1ページあたりの表示件数は1件以上で入力してください。',
            'per_page.max' => '1ページあたりの表示件数は100件以下で入力してください。',
        ];
    }
}
