<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerWithdrawalRequest extends FormRequest
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
            'status' => ['required', Rule::in(['processing', 'paid', 'failed', 'rejected'])],
            'failure_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'provider_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
