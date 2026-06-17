<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payout_beneficiary_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'payout_mobile_money_provider' => ['sometimes', 'nullable', Rule::in(['MTN', 'MOOV'])],
            'payout_mobile_money_number' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9+ ]+$/'],
            'payouts_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
