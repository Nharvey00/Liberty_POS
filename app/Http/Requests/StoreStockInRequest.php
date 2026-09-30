<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StoreStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('product_id')) {
            $product = Product::find($this->product_id);
            if ($product && $product->isAccessory()) {
                $this->merge([
                    'empty_returned_qty' => 0,
                    'empty_quantity' => 0,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity_received' => 'required|integer|min:1',
            'empty_returned_qty' => 'nullable|integer|min:0',
            'empty_quantity' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string|max:255',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        if (is_array($validated) && isset($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product && $product->isAccessory()) {
                $validated['empty_returned_qty'] = 0;
            }
        }
        return $validated;
    }
}