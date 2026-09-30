<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StoreStockOutRequest extends FormRequest
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
                    'empty_quantity_removed' => 0,
                    'empty_quantity' => 0,
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity_removed' => 'required|integer|min:0',
            'empty_quantity_removed' => 'nullable|integer|min:0',
            'empty_quantity' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $qty = (int)$this->input('quantity_removed', 0);
            $empty = (int)$this->input('empty_quantity_removed', 0);
            if ($qty <= 0 && $empty <= 0) {
                $validator->errors()->add('quantity_removed', 'At least one filled unit or empty shell must be removed.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        if (is_array($validated) && isset($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product && $product->isAccessory()) {
                $validated['empty_quantity_removed'] = 0;
            }
        }
        return $validated;
    }
}