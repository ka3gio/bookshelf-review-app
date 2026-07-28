<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'isbn' => [
                'nullable',
                'string',
                'regex:/^[0-9]{13}$/',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:255',
            'genres' => 'required|array',
            'genres.*' => 'integer|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルが長すぎます',
            'author.required' => '著者名を入力してください',
            'author.string' => '著者名は文字列である必要があります。',
            'author.max' => '著者名が長すぎます',
            // 'isbn.required' => 'ISBNを入力してください',
            'isbn.string' => 'ISBNは文字列である必要があります。',
            'isbn.regex' => 'ISBNの文字数が不正です',
            'isbn.unique' => 'その書籍は既に登録されています',
            // 'published_date.required' => '出版日を入力してください',
            'published_date.date' => '出版日を日付の形式で入力してください',
            'description.string' => '説明文は文字列である必要があります。',
            'description.max' => '説明が長すぎます',
            'image_url.url' => '画像リンクをURLで入力してください',
            'image_url.max' => '画像リンクが長すぎます',
        ];
    }
}
