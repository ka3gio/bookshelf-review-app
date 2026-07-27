<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReadingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'integer',
                Rule::in(
                    array_map(
                        fn (ReadingPlanStatus $status) => $status->value,
                        ReadingPlanStatus::cases()
                    )
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.integer' => '状態の指定が正しくありません',
            'status.in' => '状態の指定が正しくありません',
        ];
    }
}
