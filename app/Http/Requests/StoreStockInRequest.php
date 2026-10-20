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
        // Support both multi-item (items[]) and legacy single-item payloads
        if ($this->has('items') && is_array($this->input('items'))) {
            $normalizedItems = [];
            foreach ($this->input('items') as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $productId = $item['product_id'] ?? null;
                $emptyReceived = $item['empty_received_qty'] ?? ($item['empty_quantity'] ?? 0);
                $emptyReturned = $item['empty_returned_qty'] ?? 0;
                $qtyReceived   = $item['quantity_received'] ?? 0;

                if ($productId) {
                    $product = Product::find($productId);
                    if ($product && $product->isAccessory()) {
                        $emptyReceived = 0;
                        $emptyReturned = 0;
                    }
                }

                $normalizedItems[] = [
                    'product_id'         => $productId,
                    'quantity_received'  => ($qtyReceived !== '' && $qtyReceived !== null) ? $qtyReceived : 0,
                    'empty_received_qty' => ($emptyReceived !== '' && $emptyReceived !== null) ? $emptyReceived : 0,
                    'empty_returned_qty' => ($emptyReturned !== '' && $emptyReturned !== null) ? $emptyReturned : 0,
                    'unit_cost'          => ($item['unit_cost'] ?? '') !== '' ? $item['unit_cost'] : null,
                ];
            }
            $this->merge(['items' => $normalizedItems]);
            return;
        }

        if (!$this->filled('empty_received_qty') && $this->filled('empty_quantity')) {
            $this->merge([
                'empty_received_qty' => $this->input('empty_quantity'),
            ]);
        }

        if (!$this->filled('quantity_received')) {
            $this->merge(['quantity_received' => 0]);
        }

        if (!$this->filled('empty_received_qty')) {
            $this->merge(['empty_received_qty' => 0]);
        }

        if (!$this->filled('empty_returned_qty')) {
            $this->merge(['empty_returned_qty' => 0]);
        }

        if ($this->filled('product_id')) {
            $product = Product::find($this->product_id);
            if ($product && $product->isAccessory()) {
                $this->merge([
                    'empty_received_qty' => 0,
                    'empty_returned_qty' => 0,
                    'empty_quantity'     => 0,
                ]);
            }
        }
    }

    public function rules(): array
    {
        if ($this->has('items')) {
            return [
                'items'                      => 'required|array|min:1',
                'items.*.product_id'         => 'required|exists:products,id',
                'items.*.quantity_received'  => 'nullable|integer|min:0',
                'items.*.empty_received_qty' => 'nullable|integer|min:0',
                'items.*.empty_returned_qty' => 'nullable|integer|min:0',
                'items.*.unit_cost'          => 'nullable|numeric|min:0',
                'reference_no'               => 'nullable|string|max:100',
                'supplier_name'              => 'nullable|string|max:150',
                'unit_cost'                  => 'nullable|numeric|min:0',
                'remarks'                    => 'nullable|string|max:255',
            ];
        }

        return [
            'product_id'         => 'required|exists:products,id',
            'quantity_received'  => 'nullable|integer|min:0',
            'empty_received_qty' => 'nullable|integer|min:0',
            'empty_returned_qty' => 'nullable|integer|min:0',
            'empty_quantity'     => 'nullable|integer|min:0',
            'reference_no'       => 'nullable|string|max:100',
            'supplier_name'      => 'nullable|string|max:150',
            'unit_cost'          => 'nullable|numeric|min:0',
            'remarks'            => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('items') && is_array($this->input('items'))) {
                foreach ($this->input('items') as $index => $item) {
                    $filledReceived = (int) ($item['quantity_received'] ?? 0);
                    $emptyReceived  = (int) ($item['empty_received_qty'] ?? 0);

                    if ($filledReceived <= 0 && $emptyReceived <= 0) {
                        $validator->errors()->add(
                            "items.{$index}.quantity_received",
                            'Item #' . ($index + 1) . ': Please enter at least 1 Filled Tank or 1 Empty Shell received.'
                        );
                    }
                }
                return;
            }

            $filledReceived = (int) $this->input('quantity_received', 0);
            $emptyReceived  = (int) $this->input('empty_received_qty', 0);

            if ($filledReceived <= 0 && $emptyReceived <= 0) {
                $validator->errors()->add(
                    'quantity_received',
                    'Please enter at least 1 Filled Tank or 1 Empty Shell received.'
                );
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
                            $validated['items'][$idx]['empty_received_qty'] = 0;
                            $validated['items'][$idx]['empty_returned_qty'] = 0;
                        }
                    }
                }
            } elseif (isset($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
                if ($product && $product->isAccessory()) {
                    $validated['empty_received_qty'] = 0;
                    $validated['empty_returned_qty'] = 0;
                }
            }
        }
        return $validated;
    }
}