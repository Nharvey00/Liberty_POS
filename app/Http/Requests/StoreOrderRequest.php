<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer;
use App\Models\Product;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            // Strictly require customer ONLY if paying by Credit
            'customer_id'       => 'required_if:payment_method,Credit|nullable|exists:customers,id',
            'payment_method'    => 'required|in:Cash,Credit',
            'discount_amount'   => 'nullable|numeric|min:0',
            'discount_type'     => 'nullable|in:regular,senior,pwd,promo',
            'senior_id'         => 'nullable|string|max:50',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.is_swap'   => 'required|boolean',
            'items.*.residual_kg'=> 'nullable|numeric|min:0',
        ];
    }

    /**
     * Configure the validator instance with cross-field and business rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $customerId = $this->input('customer_id');
            $customer = $customerId ? Customer::find($customerId) : null;
            $isCokeResidual = $customer && in_array($customer->customer_type, ['Coke (Residual)', 'Company', 'Corporate']);
            $items = $this->input('items', []);

            if (!is_array($items)) {
                return;
            }

            $orderSubtotal = 0;

            foreach ($items as $index => $item) {
                if (!isset($item['product_id'])) {
                    continue;
                }

                $product = Product::find($item['product_id']);
                if (!$product) {
                    continue;
                }

                $qty = (int)($item['quantity'] ?? 1);
                $isSwap = filter_var($item['is_swap'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $residual = $item['residual_kg'] ?? null;
                $itemSubtotal = 0;

                // Coke (Residual) cylinder validation rules
                if ($isCokeResidual && !is_null($product->standard_capacity_kg)) {
                    if ($isSwap) {
                        // Enforce quantity = 1 per swapped tank for individual residual tracking
                        if ($qty > 1) {
                            $validator->errors()->add(
                                "items.{$index}.quantity",
                                "For Coke (Residual) accounts, swapped tanks must be recorded with quantity = 1 per line item to accurately track individual residual weights."
                            );
                        }

                        // Residual KG is strictly required for Coke (Residual) swaps
                        if (is_null($residual) || $residual === '') {
                            $validator->errors()->add(
                                "items.{$index}.residual_kg",
                                "Residual KG is required for Coke (Residual) tank swaps."
                            );
                        } elseif (!is_numeric($residual) || $residual < 0) {
                            $validator->errors()->add(
                                "items.{$index}.residual_kg",
                                "Residual KG must be a valid number greater than or equal to 0."
                            );
                        } elseif ((float)$residual > (float)$product->standard_capacity_kg) {
                            $validator->errors()->add(
                                "items.{$index}.residual_kg",
                                "Residual KG ({$residual}kg) cannot exceed the cylinder standard capacity ({$product->standard_capacity_kg}kg)."
                            );
                        }

                        $actualConsumed = max(0, $product->standard_capacity_kg - (float)($residual ?? 0));
                        $pricePerKg = $product->price / $product->standard_capacity_kg;
                        $itemSubtotal = $actualConsumed * $pricePerKg * $qty;
                    } else {
                        // Buying a new cylinder without swap: NO residual required, flat new_cylinder_price applies
                        $unitPrice = !is_null($product->new_cylinder_price) ? $product->new_cylinder_price : $product->price;
                        $itemSubtotal = $unitPrice * $qty;
                    }
                } else {
                    // Normal customer or accessory
                    if (!$isSwap && !is_null($product->new_cylinder_price)) {
                        $itemSubtotal = $product->new_cylinder_price * $qty;
                    } else {
                        $itemSubtotal = $product->price * $qty;
                    }
                }

                $orderSubtotal += $itemSubtotal;
            }

            // Fix #6: Discount cannot exceed the order subtotal
            $discount = (float)$this->input('discount_amount', 0);
            if ($discount > 0) {
                if ($discount > $orderSubtotal) {
                    $validator->errors()->add(
                        'discount_amount',
                        'Discount amount (₱' . number_format($discount, 2) . ') cannot exceed the order subtotal (₱' . number_format($orderSubtotal, 2) . ').'
                    );
                }
                
                if (!$customerId) {
                    $validator->errors()->add(
                        'customer_id',
                        'A registered customer must be selected to apply a discount.'
                    );
                }
            }

            $discountType = $this->input('discount_type');
            $seniorId = $this->input('senior_id');
            if ($discountType === 'senior' && empty($seniorId)) {
                $validator->errors()->add(
                    'senior_id',
                    'Senior Citizen ID is required when Senior Citizen discount is applied.'
                );
            }
        });
    }
    
    public function messages(): array
    {
        return [
            'customer_id.required_if' => 'A registered customer must be selected to process a Credit (Utang) transaction.',
            'items.required' => 'At least one item must be added to the cart.',
            'items.min' => 'At least one item must be added to the cart.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.quantity.integer' => 'Item quantity must be a whole number.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
        ];
    }
}