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
        if ($this->has('items') && is_array($this->input('items'))) {
            $normalizedItems = [];
            foreach ($this->input('items') as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $productId    = $item['product_id'] ?? null;
                $qtyRemoved   = $item['quantity_removed'] ?? 0;
                $emptyRemoved = $item['empty_quantity_removed'] ?? ($item['empty_quantity'] ?? 0);

                if ($productId && $this->input('reason') !== 'Loaned Tank (Huram)') {
                    $product = Product::find($productId);
                    if ($product && $product->isAccessory()) {
                        $emptyRemoved = 0;
                    }
                }

                $normalizedItems[] = [
                    'product_id'             => $productId,
                    'quantity_removed'       => ($qtyRemoved !== '' && $qtyRemoved !== null) ? $qtyRemoved : 0,
                    'empty_quantity_removed' => ($emptyRemoved !== '' && $emptyRemoved !== null) ? $emptyRemoved : 0,
                ];
            }
            $this->merge(['items' => $normalizedItems]);
            return;
        }

        if ($this->input('reason') === 'Loaned Tank (Huram)' && $this->filled('loan_quantity')) {
            $loanQty = max(0, (int) $this->input('loan_quantity', 0));
            $condition = $this->input('tank_condition_out', 'filled');
            if ($condition === 'empty_shell') {
                $this->merge([
                    'quantity_removed'       => 0,
                    'empty_quantity_removed' => $loanQty,
                ]);
            } else {
                $this->merge([
                    'quantity_removed'       => $loanQty,
                    'empty_quantity_removed' => 0,
                ]);
            }
        }

        if (!$this->filled('quantity_removed')) {
            $this->merge(['quantity_removed' => 0]);
        }

        if (!$this->filled('empty_quantity_removed')) {
            $this->merge(['empty_quantity_removed' => 0]);
        }

        if ($this->filled('product_id') && $this->input('reason') !== 'Loaned Tank (Huram)') {
            $product = Product::find($this->product_id);
            if ($product && $product->isAccessory()) {
                $this->merge([
                    'empty_quantity_removed' => 0,
                    'empty_quantity'         => 0,
                ]);
            }
        }
    }

    public function rules(): array
    {
        if ($this->has('items')) {
            return [
                'items'                          => 'required|array|min:1',
                'items.*.product_id'             => 'required|exists:products,id',
                'items.*.quantity_removed'       => 'nullable|integer|min:0',
                'items.*.empty_quantity_removed' => 'nullable|integer|min:0',
                'reference_no'                   => 'nullable|string|max:100',
                'movement_category'              => 'nullable|string|max:60',
                'reason'                         => 'required|string|max:255',
                'remarks'                        => 'nullable|string|max:255',
                'customer_id'                    => 'required_if:reason,Loaned Tank (Huram)|nullable|exists:customers,id',
                'tank_condition_out'             => 'nullable|in:filled,empty_shell',
                'loan_quantity'                  => 'nullable|integer|min:1',
            ];
        }

        return [
            'product_id'             => 'required|exists:products,id',
            'quantity_removed'       => 'nullable|integer|min:0',
            'empty_quantity_removed' => 'nullable|integer|min:0',
            'empty_quantity'         => 'nullable|integer|min:0',
            'reference_no'           => 'nullable|string|max:100',
            'movement_category'      => 'nullable|string|max:60',
            'reason'                 => 'required|string|max:255',
            'remarks'                => 'nullable|string|max:255',
            'customer_id'            => 'required_if:reason,Loaned Tank (Huram)|nullable|exists:customers,id',
            'tank_condition_out'     => 'required_if:reason,Loaned Tank (Huram)|nullable|in:filled,empty_shell',
            'loan_quantity'          => 'nullable|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $isHuram = $this->input('reason') === 'Loaned Tank (Huram)';

            if ($this->has('items') && is_array($this->input('items'))) {
                foreach ($this->input('items') as $index => $item) {
                    $qty   = (int) ($item['quantity_removed'] ?? 0);
                    $empty = (int) ($item['empty_quantity_removed'] ?? 0);
                    if ($qty <= 0 && $empty <= 0) {
                        $validator->errors()->add(
                            "items.{$index}.quantity_removed",
                            'Item #' . ($index + 1) . ': At least one filled unit or empty shell must be removed.'
                        );
                    }

                    if ($isHuram && !empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->isAccessory()) {
                            $validator->errors()->add(
                                "items.{$index}.product_id",
                                'Only LPG cylinders can be loaned as Huram tanks.'
                            );
                        }
                    }
                }
                return;
            }

            $qty = (int)$this->input('quantity_removed', 0);
            $empty = (int)$this->input('empty_quantity_removed', 0);
            if ($qty <= 0 && $empty <= 0) {
                $validator->errors()->add('quantity_removed', 'At least one filled unit or empty shell must be removed.');
            }

            if ($isHuram && $this->filled('product_id')) {
                $product = Product::find($this->product_id);
                if ($product && $product->isAccessory()) {
                    $validator->errors()->add('product_id', 'Only LPG cylinders can be loaned as Huram tanks.');
                }
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        if (is_array($validated)) {
            if (isset($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $idx => $item) {
                    if (isset($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product && $product->isAccessory()) {
                            $validated['items'][$idx]['empty_quantity_removed'] = 0;
                        }
                    }
                }
            } elseif (isset($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
                if ($product && $product->isAccessory()) {
                    $validated['empty_quantity_removed'] = 0;
                }
            }
        }
        return $validated;
    }
}