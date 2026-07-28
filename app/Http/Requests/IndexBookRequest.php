<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'genre' => 'nullable|integer|exists:genres,id',
            'sort' => 'nullable|in:latest,oldest,rating,title',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'genre.integer' => 'ジャンルIDを数値で指定して下さい',
            'genre.exists' => '指定されたジャンルは存在しません',
            'sort.in' => '指定された並び順が不正です',
        ];
    }
}
