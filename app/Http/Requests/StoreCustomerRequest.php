<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'customer_type' => 'required|string|in:Normal,Company',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:255',
        ];
    }
}