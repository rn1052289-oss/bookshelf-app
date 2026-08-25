<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 書籍登録時のバリデーションルールを返す。
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('books', 'isbn'),
            ],
            'published_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    /**
     * 日本語のバリデーションメッセージを返す。
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNは必須です。',
            'isbn.regex' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',
            'published_date.date' => '出版日は正しい日付で入力してください。',
            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genre_ids.required' => 'ジャンルを1件以上選択してください。',
            'genre_ids.array' => 'ジャンルを正しく選択してください。',
            'genre_ids.min' => 'ジャンルを1件以上選択してください。',
            'genre_ids.*.integer' => 'ジャンルを正しく選択してください。',
            'genre_ids.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
