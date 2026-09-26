<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Point of Sale</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Process walk-in and delivery transactions</div>
    </x-slot>

    @if($errors->any())
        <div class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="posEngine()" class="flex flex-col lg:flex-row gap-6 items-start">
        
        <div class="w-full lg:w-3/5 bg-white border border-[#E5E9EF] rounded-[16px] p-6 h-fit">
            <h3 class="text-[14.5px] font-bold text-[#1C2430] mb-4">Inventory Items</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($products as $product)
                    <button type="button" 
                        @click="addToCart({{ $product }})"
                        class="p-4 border border-[#E5E9EF] rounded-xl text-left bg-white hover:border-[#0B3B70] hover:bg-[#E7EEF7] transition-colors focus:outline-none flex flex-col justify-between h-full">
                        <div>
                            <h4 class="font-bold text-[13px] text-[#1C2430] mb-1 leading-tight">{{ $product->name }}</h4>
                            <p class="text-[12.5px] text-[#5B6472]">₱{{ number_format($product->price, 2) }}</p>
                        </div>
                        <p class="text-[11px] font-semibold mt-3 bg-[#F4F6F9] text-[#0B3B70] inline-block px-2 py-0.5 rounded w-full text-center">Stock: {{ $product->stock_quantity }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="w-full lg:w-2/5 bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden sticky top-24 shadow-sm h-[calc(100vh-140px)] flex flex-col">
            <div class="px-5 py-4 border-b border-[#E5E9EF] bg-[#F4F6F9]">
                <h3 class="text-[14.5px] font-bold text-[#1C2430]">Current Order</h3>
            </div>

            <form method="POST" action="{{ route('pos.store') }}" id="checkout-form" class="flex flex-col h-full overflow-hidden">
                @csrf
                <div class="p-5 flex-1 overflow-y-auto">
                    <div class="mb-5">
                        <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5 uppercase tracking-[0.02em]">Customer (Optional for Cash)</label>
                        <select name="customer_id" x-model="selectedCustomerId" @change="checkCustomerType()" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="">-- Walk-in Customer --</option>
                            <template x-for="customer in customers" :key="customer.id">
                                <option :value="customer.id" x-text="customer.name + (customer.business_name ? ' (' + customer.business_name + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <div class="space-y-3">
                        <template x-if="cart.length === 0">
                            <div class="text-[12.5px] text-[#5B6472] py-6 text-center border border-dashed border-[#E5E9EF] rounded-lg">
                                Cart is empty. Tap products to add.
                            </div>
                        </template>

                        <template x-for="(item, index) in cart" :key="index">
                            <div class="p-3 border border-[#E5E9EF] rounded-xl bg-[#F4F6F9]">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-bold text-[13px] text-[#1C2430]" x-text="item.name"></span>
                                    <button type="button" @click="removeFromCart(index)" class="text-[#B5504B] text-[11.5px] font-bold hover:underline bg-transparent border-none ml-2">✕ Remove</button>
                                </div>
                                
                                <div class="flex items-center gap-3 mb-2">
                                    <label class="text-[11.5px] font-semibold text-[#5B6472]">Qty:</label>
                                    <input type="number" 
                                           x-model.number="item.quantity" 
                                           :max="(isCompany && item.standard_capacity_kg !== null && item.is_swap) ? 1 : null" 
                                           :readonly="isCompany && item.standard_capacity_kg !== null && item.is_swap" 
                                           class="w-16 px-2 py-1 text-[13px] border border-[#E5E9EF] rounded-md focus:ring-[#0B3B70]" 
                                           min="1" required>
                                    <template x-if="isCompany && item.standard_capacity_kg !== null && item.is_swap">
                                        <span class="text-[10.5px] text-[#5B6472] italic">(1 tank per line for individual residual tracking)</span>
                                    </template>
                                </div>

                                <template x-if="item.new_cylinder_price !== null">
                                    <div class="flex items-center gap-2 mb-2 bg-white px-2 py-1.5 rounded border border-[#E5E9EF]">
                                        <input type="checkbox" x-model="item.is_swap" class="rounded border-[#E5E9EF] text-[#0B3B70] focus:ring-[#0B3B70]">
                                        <label class="text-[11.5px] font-semibold text-[#1C2430]">Tank Swap (Customer gave empty)</label>
                                    </div>
                                </template>

                                {{-- Fix #1 & #7: Only show residual KG if isCompany AND standard_capacity_kg exists AND item.is_swap is TRUE --}}
                                <template x-if="isCompany && item.standard_capacity_kg !== null && item.is_swap">
                                    <div class="mt-2 p-2 bg-[#FBF0DD] border border-[#B4700A]/30 rounded-lg">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-[#B4700A] block">
                                                Company Residual (KG) <span class="text-[#B5504B]">*</span>
                                            </label>
                                            <span class="text-[10px] text-[#5B6472]" x-text="'Capacity: ' + item.standard_capacity_kg + 'kg'"></span>
                                        </div>
                                        <input type="number" step="0.01" min="0" :max="item.standard_capacity_kg" x-model.number="item.residual_kg" class="w-full px-2 py-1 text-[13px] border border-[#E5E9EF] rounded bg-white focus:ring-[#B4700A]" placeholder="Enter residual weight" :required="isCompany && item.is_swap">
                                    </div>
                                </template>

                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                                <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                <input type="hidden" :name="`items[${index}][is_swap]`" :value="item.is_swap ? 1 : 0">
                                <input type="hidden" :name="`items[${index}][residual_kg]`" :value="item.residual_kg">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="p-5 border-t border-[#E5E9EF] bg-white shrink-0">
                    <div class="mb-4 flex justify-between items-center bg-[#F4F6F9] px-3 py-2 rounded-lg border border-[#E5E9EF]">
                        <label class="text-[12.5px] font-bold text-[#1C2430]">Less Discount (₱):</label>
                        <input type="number" name="discount_amount" x-model.number="discount" :max="calculateSubtotal()" class="w-24 text-right px-2 py-1.5 border border-[#E5E9EF] rounded-md text-[13px] focus:ring-[#0B3B70]" min="0" step="0.01" placeholder="0.00">
                    </div>

                    <div class="mb-5">
                        <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5 uppercase tracking-[0.02em]">Payment Method <span class="text-[#B5504B]">*</span></label>
                        <select name="payment_method" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13px] font-semibold bg-[#F4F6F9] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="Cash">Cash (Paid Now)</option>
                            <option value="Credit">Credit (Add to Utang Ledger)</option>
                        </select>
                    </div>

                    <div class="flex justify-between items-center mb-5 border-t border-dashed border-[#E5E9EF] pt-4">
                        <span class="font-['Manrope'] font-bold text-[16px] text-[#5B6472] uppercase tracking-[0.02em]">Total Due:</span>
                        <span x-text="'₱' + calculateTotal().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})" class="font-['Manrope'] font-extrabold text-[24px] text-[#0B3B70]"></span>
                    </div>

                    <button type="submit" :disabled="cart.length === 0" class="w-full rounded-lg px-[15px] py-[12px] text-[14px] font-bold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Process Checkout
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function posEngine() {
            return {
                customers: @json($customers),
                selectedCustomerId: '',
                isCompany: false,
                cart: [],
                discount: 0,

                checkCustomerType() {
                    const customer = this.customers.find(c => c.id == this.selectedCustomerId);
                    this.isCompany = customer ? (customer.customer_type === 'Company') : false;
                },

                addToCart(product) {
                    // Fix #7: If corporate account and product is an LPG cylinder, add each cylinder as an independent line item with qty=1
                    const isCorporateCylinder = this.isCompany && product.standard_capacity_kg !== null;
                    const existingItem = isCorporateCylinder ? null : this.cart.find(item => item.id === product.id);
                    
                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: parseFloat(product.price),
                            new_cylinder_price: product.new_cylinder_price !== null ? parseFloat(product.new_cylinder_price) : null,
                            standard_capacity_kg: product.standard_capacity_kg !== null ? parseFloat(product.standard_capacity_kg) : null,
                            quantity: 1,
                            is_swap: product.new_cylinder_price !== null,
                            residual_kg: null
                        });
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                calculateSubtotal() {
                    let total = 0;
                    this.cart.forEach(item => {
                        let subtotal = 0;
                        if (this.isCompany && item.standard_capacity_kg !== null && item.is_swap && item.residual_kg !== null && item.residual_kg !== '') {
                            const actualConsumed = Math.max(0, item.standard_capacity_kg - parseFloat(item.residual_kg || 0));
                            const pricePerKg = item.price / item.standard_capacity_kg;
                            subtotal = actualConsumed * pricePerKg * item.quantity;
                        } else {
                            subtotal = item.price * item.quantity;
                        }
                        
                        if (item.new_cylinder_price !== null && !item.is_swap) {
                            subtotal += (item.new_cylinder_price * item.quantity);
                        }
                        
                        total += subtotal;
                    });
                    return total;
                },

                calculateTotal() {
                    const subtotal = this.calculateSubtotal();
                    return Math.max(0, subtotal - (this.discount || 0));
                }
            }
        }
    </script>
</x-app-layout>