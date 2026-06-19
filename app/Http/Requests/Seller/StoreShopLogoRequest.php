<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,webp',
                'max:2048', // 2MB
                'dimensions:min_width=200,min_height=200,max_width=1000,max_height=1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'The logo file is required.',
            'logo.file' => 'The logo must be a file.',
            'logo.mimes' => 'The logo must be a JPEG, PNG, JPG, or WEBP image.',
            'logo.max' => 'The logo may not be greater than 2MB.',
            'logo.dimensions' => 'The logo must be at least 200x200 pixels and at most 1000x1000 pixels.',
        ];
    }
}
