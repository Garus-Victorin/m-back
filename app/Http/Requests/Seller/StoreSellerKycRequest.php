<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreSellerKycRequest extends FormRequest
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
            'document_type' => ['required', Rule::in(['national_id', 'passport', 'business_registration'])],
            'document_number' => ['nullable', 'string', 'max:100'],
            'document_front_path' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                $this->validatePrivateKycPath($attribute, $value, $fail);
            }],
            'document_back_path' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value !== null) {
                    $this->validatePrivateKycPath($attribute, $value, $fail);
                }
            }],
            'mobile_money_provider' => ['required', Rule::in(['MTN', 'MOOV'])],
            'mobile_money_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+ ]+$/'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validatePrivateKycPath(string $attribute, mixed $value, \Closure $fail): void
    {
        $userId = $this->user()?->id;
        $path = is_string($value) ? trim($value) : '';

        if ($path === '' || ! $userId || ! str_starts_with($path, 'kyc/'.$userId.'/')) {
            $fail("The {$attribute} must reference a private KYC file uploaded by the current seller.");

            return;
        }

        if (! Storage::disk('local')->exists($path)) {
            $fail("The selected {$attribute} file does not exist.");
        }
    }
}
