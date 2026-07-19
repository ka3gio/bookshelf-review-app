<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:100',
            'genre_id' => 'nullable|integer|exists:genres,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは100文字以内で入力して下さい',
            'genre_id.integer' => 'ジャンルIDは整数である必要があります',
            'genre_id.exists' => '指定されたジャンルIDは存在しません',
            'page.integer' => 'ページ番号は整数である必要があります',
            'page.min' => 'ページ番号は1以上である必要があります',
            'per_page.integer' => '1ページあたりの件数は整数である必要があります',
            'per_page.min' => '1ページあたりの件数は1以上である必要があります',
            'per_page.max' => '1ページあたりの件数は100以下である必要があります',
        ];
    }
}
