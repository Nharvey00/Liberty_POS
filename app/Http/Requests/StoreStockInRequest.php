<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity_received' => 'required|integer|min:1',
            'empty_returned_qty' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}