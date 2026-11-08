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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'customer_type' => 'required|string|in:Tertiary,Household,Industrial,MRO,Service Station,Commercial,Main Store,Coke (Residual),Company,Corporate,Normal,Retail,Individual',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:255',
            'default_discount_type' => 'nullable|string|in:none,senior,pwd,regular,tertiary,industrial,promo',
            'default_discount_percentage' => 'nullable|numeric|min:0',
            'default_discount_ref_name' => 'nullable|string|max:255',
            'default_discount_ref_id' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = strtolower((string) $this->input('default_discount_type', ''));
            $value = $this->input('default_discount_percentage');

            if (in_array($type, ['senior', 'pwd'], true) && $value !== null && $value !== '' && (float) $value > 100) {
                $validator->errors()->add(
                    'default_discount_percentage',
                    'Percentage discount for Senior/PWD cannot exceed 100%.'
                );
            }
        });
    }
}