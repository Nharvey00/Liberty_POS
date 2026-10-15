<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'sku'                  => 'nullable|string|max:80|unique:products,sku',
            'price'                => 'required|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'new_cylinder_price'   => 'nullable|numeric|min:0',
            'standard_capacity_kg' => 'nullable|numeric|gt:0',
            'stock_quantity'       => 'nullable|integer|min:0',
            'empty_quantity'       => 'nullable|integer|min:0',
            'reorder_level'        => 'nullable|integer|min:0',
        ];
    }
}