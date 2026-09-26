<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatementRequest extends FormRequest
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
            'credit_account_id'    => ['required', 'integer', Rule::exists('credit_accounts', 'id')],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end'   => ['required', 'date', 'after_or_equal:billing_period_start'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'billing_period_end.after_or_equal' => 'The billing end date must be on or after the start date.',
            'credit_account_id.exists'           => 'The selected credit account does not exist.',
        ];
    }
}
