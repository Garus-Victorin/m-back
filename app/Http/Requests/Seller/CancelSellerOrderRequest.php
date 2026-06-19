<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelSellerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:500',
                Rule::in([
                    'customer_requested',
                    'out_of_stock',
                    'shipping_issue',
                    'payment_failed',
                    'fraud_suspected',
                    'other',
                ]),
            ],
            'details' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A cancellation reason is required.',
            'reason.in' => 'The selected reason is not valid.',
            'details.max' => 'The details may not be greater than 2000 characters.',
        ];
    }
}
