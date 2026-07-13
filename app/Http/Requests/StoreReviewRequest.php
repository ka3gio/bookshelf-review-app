<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください',
            'rating.integer' => '評価は1〜5の整数で選択してください',
            'rating.between' => '評価は1〜5の整数で選択してください',
            'comment.required' => 'コメントを入力してください',
            'comment.max' => 'コメントが長すぎます',
        ];
    }
}
