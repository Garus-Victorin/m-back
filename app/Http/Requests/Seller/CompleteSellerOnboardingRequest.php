<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteSellerOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop' => [
                'required',
                'array',
            ],
            'shop.name' => [
                'required',
                'string',
                'max:100',
            ],
            'shop.description' => [
                'required',
                'string',
                'max:1000',
            ],
            'shop.phone' => [
                'required',
                'string',
                'max:20',
            ],
            'shop.address' => [
                'required',
                'string',
                'max:255',
            ],
            'shop.city' => [
                'required',
                'string',
                'max:100',
            ],
            'shop.country' => [
                'required',
                'string',
                'max:100',
            ],
            'payout' => [
                'required',
                'array',
            ],
            'payout.provider' => [
                'required',
                'string',
                Rule::in(['MTN', 'Orange Money', 'Moov Money', 'Wave', 'other']),
            ],
            'payout.number' => [
                'required',
                'string',
                'max:20',
            ],
            'kyc' => [
                'nullable',
                'array',
            ],
            'kyc.document_type' => [
                'nullable',
                'string',
                Rule::in(['id_card', 'passport', 'driver_license']),
            ],
            'kyc.document_front' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:5120', // 5MB
            ],
            'kyc.document_back' => [
                'nullable',
                'file',
                'mimes:jpeg,png,jpg,pdf',
                'max:5120', // 5MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'shop.name.required' => 'The shop name is required.',
            'shop.description.required' => 'The shop description is required.',
            'payout.provider.required' => 'The payout provider is required.',
            'payout.number.required' => 'The payout number is required.',
            'kyc.document_front.max' => 'The document file may not be greater than 5MB.',
            'kyc.document_back.max' => 'The document file may not be greater than 5MB.',
        ];
    }
}
