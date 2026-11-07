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
        if ($this->input('reason') === 'Loaned Tank (Huram)' && $this->filled('loan_quantity')) {
            $loanQty = max(0, (int) $this->input('loan_quantity', 0));
            $condition = $this->input('tank_condition_out', 'filled');
            if ($condition === 'empty_shell') {
                $this->merge([
                    'quantity_removed' => 0,
                    'empty_quantity_removed' => $loanQty,
                ]);
            } else {
                $this->merge([
                    'quantity_removed' => $loanQty,
                    'empty_quantity_removed' => 0,
                ]);
            }
        }

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
            'customer_id' => 'required_if:reason,Loaned Tank (Huram)|nullable|exists:customers,id',
            'tank_condition_out' => 'required_if:reason,Loaned Tank (Huram)|nullable|in:filled,empty_shell',
            'loan_quantity' => 'nullable|integer|min:1',
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

            if ($this->input('reason') === 'Loaned Tank (Huram)' && $this->filled('product_id')) {
                $product = Product::find($this->product_id);
                if ($product && $product->isAccessory()) {
                    $validator->errors()->add('product_id', 'Only cylinder/tank products can be loaned as Huram tanks.');
                }
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