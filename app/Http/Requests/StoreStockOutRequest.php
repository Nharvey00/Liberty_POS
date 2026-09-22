<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity_removed' => 'required|integer|min:0',
            'empty_quantity_removed' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:255',
        ];
    }
}