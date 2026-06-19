<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StoreKycSelfieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selfie' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg',
                'max:5120', // 5MB
                'dimensions:min_width=300,min_height=300',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'selfie.required' => 'The selfie file is required.',
            'selfie.file' => 'The selfie must be a file.',
            'selfie.mimes' => 'The selfie must be a JPEG, PNG, or JPG image.',
            'selfie.max' => 'The selfie may not be greater than 5MB.',
            'selfie.dimensions' => 'The selfie must be at least 300x300 pixels.',
        ];
    }
}
