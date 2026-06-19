<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'images' => [
                'nullable',
                'array',
            ],
            'images.*' => [
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'The rating is required.',
            'rating.integer' => 'The rating must be an integer.',
            'rating.min' => 'The rating must be at least 1.',
            'rating.max' => 'The rating may not be greater than 5.',
            'comment.max' => 'The comment may not be greater than 1000 characters.',
            'images.*.max' => 'Each image URL may not be greater than 500 characters.',
        ];
    }
}
