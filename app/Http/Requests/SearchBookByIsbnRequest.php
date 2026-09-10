<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchBookByIsbnRequest extends FormRequest
{
    /**
     * リクエストを認可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ISBN検索のバリデーションルールを定義する。
     */
    public function rules(): array
    {
        return [
            'isbn' => ['required', 'regex:/^\d{13}$/'],
        ];
    }

    /**
     * URLパラメータのISBNをバリデーション対象へ追加する。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => $this->route('isbn'),
        ]);
    }

    /**
     * バリデーション失敗時に既存仕様のJSONを返す。
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422)
        );
    }
}
