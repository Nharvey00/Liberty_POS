<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'credit_limit'           => ['nullable', 'numeric', 'min:0'],
            'agreed_monthly_payment' => ['nullable', 'numeric', 'min:0'],
            'is_active'              => ['required', 'boolean'],
            'status'                 => ['nullable', 'string', 'in:pending,approved,rejected,suspended,closed'],
            'approval_remarks'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
