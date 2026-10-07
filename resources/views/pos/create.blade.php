<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Point of Sale</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Process walk-in and delivery transactions</div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-[#EAF5EF] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="posEngine()">
        <div class="flex flex-col lg:flex-row gap-5 items-start">
            
            <!-- Left Column: Inventory List (60% width with dedicated vertical scroll) -->
        <div class="w-full lg:w-[60%] flex flex-col bg-white border border-[#E5E9EF] rounded-[16px] p-5 lg:h-[calc(100vh-175px)] lg:min-h-[580px] shadow-xs">
            <div class="flex items-center justify-between mb-3 shrink-0">
                <h3 class="font-sans text-[15px] font-bold text-gray-900">Inventory Items</h3>
                <span class="text-[11.5px] text-[#64748B]">{{ count($products) }} items available</span>
            </div>
            <!-- Scrollable Product Grid inside Left Column -->
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-3">
                    @foreach($products as $product)
                        <button type="button" 
                            @click="addToCart({{ $product }})"
                            class="p-3.5 border border-[#E5E9EF] rounded-xl text-left bg-white hover:border-[#0B3B70] hover:bg-[#E7EEF7] transition-all focus:outline-none flex flex-col justify-between h-full group cursor-pointer shadow-2xs hover:shadow-xs">
                            <div>
                                <h4 class="font-bold text-[13px] text-[#1C2430] group-hover:text-[#0B3B70] mb-1 leading-snug line-clamp-2">{{ $product->name }}</h4>
                                <p class="text-[12.5px] font-semibold text-[#5B6472]">₱{{ number_format($product->price, 2) }}</p>
                            </div>
                            <div class="mt-2.5">
                                <span class="text-[10.5px] font-bold bg-[#F4F6F9] text-[#0B3B70] inline-block px-2 py-0.5 rounded w-full text-center group-hover:bg-white/80">Stock: {{ $product->stock_quantity }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column: Cart & Checkout (40% width, sticky panel, internal scrolling) -->
        <div class="w-full lg:w-[40%] bg-white border border-[#CBD5E1] rounded-[16px] overflow-hidden lg:sticky lg:top-4 shadow-md flex flex-col lg:h-[calc(100vh-175px)] lg:min-h-[580px]">
            <!-- Cart Header -->
            <div class="px-4 py-3 border-b border-[#E2E8F0] bg-[#F8FAFC] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#EAF0F9] text-[#0B3B70] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-sans text-[14px] font-bold text-gray-900 leading-tight">Current Order</h3>
                        <p class="text-[10.5px] text-[#64748B]">Cart &amp; Checkout Summary</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span x-show="cart.length > 0" 
                          class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#0B3B70] text-white" 
                          x-text="cart.reduce((sum, item) => sum + item.quantity, 0) + ' item(s)'"></span>
                    <button type="button" 
                            x-show="cart.length > 0" 
                            @click="cart = []" 
                            class="text-[11px] font-semibold text-[#B5504B] hover:text-[#913b37] hover:underline cursor-pointer">
                        Clear All
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('pos.store') }}" id="checkout-form" x-ref="checkoutForm" @submit.prevent="openCheckoutModal()" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                
                <!-- Customer Select (Fixed at top of cart) -->
                <div class="px-4 py-2 border-b border-[#F1F5F9] bg-white shrink-0">
                    <label class="block text-[10px] font-bold text-[#475569] uppercase tracking-wider mb-1">
                        Customer <span class="text-[#94A3B8] font-normal normal-case">(Required for Credit / Discounts)</span>
                    </label>
                    <select name="customer_id" x-model="selectedCustomerId" @change="checkCustomerType()" class="w-full px-2.5 py-1.5 border border-[#CBD5E1] rounded-lg text-[12px] bg-white focus:ring-1 focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                        <option value="">-- Walk-in Customer --</option>
                        <template x-for="customer in customers" :key="customer.id">
                            <option :value="customer.id" x-text="customer.name + (customer.business_name ? ' (' + customer.business_name + ')' : '')"></option>
                        </template>
                    </select>
                </div>

                <!-- Scrollable Items Area (High-Density UI, Compact Single-Row Items) -->
                <div class="px-3.5 py-2 flex-1 overflow-y-auto min-h-0">
                    <!-- Cart Items Column Header (Visible when cart has items) -->
                    <div x-show="cart.length > 0" class="grid grid-cols-12 gap-2 text-[10px] font-bold text-[#64748B] uppercase tracking-wider px-2 py-1.5 bg-[#F1F5F9] rounded border border-[#E2E8F0] mb-2 items-center">
                        <div class="col-span-4">Item Name</div>
                        <div class="col-span-2 text-center">Unit Price</div>
                        <div class="col-span-3 text-center">Quantity</div>
                        <div class="col-span-2 text-right">Subtotal</div>
                        <div class="col-span-1 text-center"><span class="sr-only">Remove Item</span></div>
                    </div>

                    <template x-if="cart.length === 0">
                        <div class="py-10 px-4 text-center border-2 border-dashed border-[#E2E8F0] rounded-xl bg-[#F8FAFC]">
                            <div class="w-10 h-10 mx-auto rounded-full bg-[#E2E8F0]/60 flex items-center justify-center text-[#94A3B8] mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h4 class="font-bold text-[13px] text-[#334155] mb-0.5">Cart is empty</h4>
                            <p class="text-[11.5px] text-[#64748B]">Click any inventory item to add it to this order.</p>
                        </div>
                    </template>

                    <div class="space-y-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="border border-[#CBD5E1] rounded-lg p-2.5 bg-white hover:border-[#94A3B8] transition-all shadow-2xs">
                                <!-- Compact Single-Row Grid -->
                                <div class="grid grid-cols-12 items-center gap-2">
                                    <!-- Item Name & detail -->
                                    <div class="col-span-4 min-w-0 pr-1">
                                        <div class="font-bold text-[13px] text-gray-900 truncate leading-tight" :title="item.name" x-text="item.name"></div>
                                        <div class="text-[10px] text-[#64748B] mt-0.5">
                                            <span x-text="item.is_cylinder ? (item.is_swap ? 'Refill' : 'New Tank') : 'Retail'"></span>
                                        </div>
                                    </div>

                                    <!-- Unit Price -->
                                    <div class="col-span-2 text-center">
                                        <span class="text-sm font-medium text-gray-500" x-text="'₱' + getItemUnitPrice(item).toFixed(2)"></span>
                                    </div>

                                    <!-- Quantity Stepper [- Qty +] -->
                                    <div class="col-span-3 flex justify-center">
                                        <div class="inline-flex items-center bg-[#F8FAFC] border border-[#CBD5E1] rounded shadow-2xs overflow-hidden">
                                            <button type="button" 
                                                    @click="decrementQty(item)" 
                                                    :disabled="item.quantity <= 1 || (isCompany && item.standard_capacity_kg !== null && item.is_swap)"
                                                    class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold border-r border-[#CBD5E1]">
                                                –
                                            </button>
                                            <input type="number" 
                                                   x-model.number="item.quantity" 
                                                   :max="(isCompany && item.standard_capacity_kg !== null && item.is_swap) ? 1 : null" 
                                                   :readonly="isCompany && item.standard_capacity_kg !== null && item.is_swap" 
                                                   class="w-10 h-8 text-center font-bold text-[12px] border-0 p-0 focus:ring-0 text-gray-900 bg-transparent" 
                                                   min="1" required>
                                            <button type="button" 
                                                    @click="incrementQty(item)" 
                                                    :disabled="isCompany && item.standard_capacity_kg !== null && item.is_swap"
                                                    class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed text-sm font-bold border-l border-[#CBD5E1]">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="col-span-2 text-right">
                                        <span class="text-[13px] font-bold text-gray-900" x-text="'₱' + getItemSubtotal(item).toFixed(2)"></span>
                                    </div>

                                    <!-- Remove Item Button -->
                                    <div class="col-span-1 flex justify-end">
                                        <button type="button" 
                                                @click="removeFromCart(index)" 
                                                class="p-2 text-[#B5504B] hover:text-white hover:bg-[#B5504B] rounded transition-colors cursor-pointer flex items-center justify-center" 
                                                title="Remove Item">
                                            <span class="sr-only">Remove Item</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Tank Swap Toggle (Cylinders Only) -->
                                <template x-if="item.is_cylinder && item.standard_capacity_kg !== null">
                                    <div class="mt-1 pt-1 border-t border-dashed border-[#E2E8F0] flex items-center justify-between text-[10px]">
                                        <label class="inline-flex items-center gap-1 font-medium text-[#334155] cursor-pointer">
                                            <input type="checkbox" x-model="item.is_swap" class="rounded border-[#CBD5E1] text-[#0B3B70] focus:ring-[#0B3B70] w-3 h-3">
                                            <span>Tank Swap (Empty returned)</span>
                                        </label>
                                        <span class="px-1.5 py-0.2 rounded font-bold" 
                                              :class="item.is_swap ? 'bg-[#E5F5EC] text-[#1E8E5A]' : 'bg-[#EAF0F9] text-[#0B3B70]'" 
                                              x-text="item.is_swap ? 'Refill Rate' : 'New Tank Rate'"></span>
                                    </div>
                                </template>

                                <!-- Coke Residual (For Residual Accounts) -->
                                <template x-if="isCompany && item.is_cylinder && item.standard_capacity_kg !== null && item.is_swap">
                                    <div class="mt-1 p-1.5 bg-[#FFFBEB] border border-[#FDE68A] rounded text-[10px] flex items-center justify-between gap-1.5">
                                        <span class="font-bold text-[#B45309]">Tare (kg) <span class="text-red-500">*</span>:</span>
                                        <input type="number" step="0.01" min="0" :max="item.standard_capacity_kg" x-model.number="item.residual_kg" class="w-20 px-1 py-0.5 text-[11px] font-semibold border border-[#FCD34D] rounded bg-white text-right" placeholder="0.00" :required="isCompany && item.is_swap">
                                    </div>
                                </template>

                                <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                                <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                                <input type="hidden" :name="`items[${index}][is_swap]`" :value="(item.is_cylinder && item.is_swap) ? 1 : 0">
                                <input type="hidden" :name="`items[${index}][residual_kg]`" :value="item.is_cylinder ? item.residual_kg : ''">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Fixed Footer / Discount & Checkout (Anchored at the bottom) -->
                <div class="p-3.5 border-t border-[#E2E8F0] bg-white shrink-0 shadow-lg space-y-2.5">
                    <!-- Original Discount Logic Workflow -->
                    <div class="p-2.5 bg-[#F8FAFC] border border-[#E2E8F0] rounded-xl space-y-2">
                        <!-- Discount Type dropdown FIRST. Defaults to "None" -->
                        <div class="flex items-center gap-2">
                            <label class="block text-[10.5px] font-bold text-[#334155] uppercase tracking-wider shrink-0">
                                Discount Type
                            </label>
                            <select name="discount_type" 
                                    x-model="discountType" 
                                    @change="onDiscountTypeChange()" 
                                    class="flex-1 px-2.5 py-1.5 border border-[#CBD5E1] rounded-lg text-[12px] font-medium bg-white focus:ring-1 focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                                <option value="none">None</option>
                                <option value="regular">Regular</option>
                                <option value="senior">Senior</option>
                                <option value="pwd">PWD</option>
                                <option value="promo">Promo</option>
                            </select>
                        </div>

                        <!-- Dynamic UI logic: Discount Amount number input ONLY appears if user selects type other than "None" -->
                        <div x-show="discountType !== 'none'" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pt-1.5 border-t border-[#E2E8F0] space-y-2">
                            <div class="flex items-center gap-2">
                                <label class="text-[11px] font-bold text-[#334155] shrink-0">Discount Amount:</label>
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-500 font-bold text-[12px]">₱</div>
                                    <input type="number" 
                                           name="discount_amount"
                                           x-model.number="discountValue" 
                                           :min="0" 
                                           :max="calculateSubtotal()" 
                                           step="0.01" 
                                           placeholder="0.00" 
                                           class="w-full pl-7 pr-2 py-1 border border-[#CBD5E1] rounded-lg text-[12px] font-semibold bg-white focus:ring-1 focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                                </div>
                            </div>
                            
                            <input type="hidden" name="discount_reference_name" :value="discountRefName">
                            <input type="hidden" name="discount_reference_id" :value="discountRefId">
                            
                            <template x-if="['senior', 'pwd'].includes(discountType)">
                                <div class="pt-1.5 border-t border-dashed border-[#E2E8F0]">
                                    <button type="button" @click="showDiscountModal = true" class="w-full py-2 flex items-center justify-center gap-2 text-[11px] font-bold text-[#0B3B70] bg-[#E7EEF7] hover:bg-[#D4E0F0] rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        <span x-text="discountRefName ? 'Edit Details (' + discountRefName + ')' : 'Add ' + (discountType === 'senior' ? 'Senior' : 'PWD') + ' Details'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="flex items-center gap-2">
                        <label class="block text-[10.5px] font-bold text-[#475569] uppercase tracking-wider shrink-0">
                            Payment Method <span class="text-[#B5504B]">*</span>
                        </label>
                        <select name="payment_method" required class="flex-1 px-2.5 py-1.5 border border-[#CBD5E1] rounded-lg text-[12px] font-semibold bg-[#F8FAFC] focus:ring-1 focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="Cash">Cash (Paid Now)</option>
                            <option value="Credit">Credit (Add to Utang Ledger)</option>
                        </select>
                    </div>

                    <!-- Financial Summary & Total Due -->
                    <div class="border-t border-dashed border-[#CBD5E1] pt-2 space-y-1">
                        <div class="flex justify-between items-center text-[11.5px] text-[#64748B]">
                            <span>Subtotal:</span>
                            <span class="font-bold text-[#1E293B]" x-text="'₱' + calculateSubtotal().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        </div>
                        <template x-if="calculateDiscountAmount() > 0">
                            <div class="flex justify-between items-center text-[11.5px] text-[#B5504B] font-semibold">
                                <span x-text="'Less Discount (' + discountType.charAt(0).toUpperCase() + discountType.slice(1) + '):'"></span>
                                <span class="font-bold" x-text="'-₱' + calculateDiscountAmount().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                            </div>
                        </template>
                        <div class="flex justify-between items-center pt-1.5 border-t border-[#E2E8F0]">
                            <span class="font-sans font-bold text-xs text-[#475569] uppercase tracking-wider">Total Due:</span>
                            <span x-text="'₱' + calculateTotal().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})" class="text-2xl font-black font-sans text-gray-900 tracking-tight"></span>
                        </div>
                    </div>

                    <!-- Process Checkout Button -->
                    <button type="button" 
                            @click.prevent="openCheckoutModal()" 
                            :disabled="cart.length === 0 || isSubmitting" 
                            :class="{ 'opacity-50 cursor-not-allowed': isSubmitting || cart.length === 0 }"
                            class="w-full rounded-xl py-2.5 text-[14px] font-bold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm cursor-pointer flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Process Checkout</span>
                    </button>
                </div>
            </form>
        </div>
    </div> <!-- Close flex container -->

    <!-- Checkout Confirmation Modal -->
    <div x-show="showConfirmModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4" 
             style="display: none;"
             @keydown.escape.window="if (!isSubmitting) showConfirmModal = false">
            
            <!-- Modal Backdrop Click Dismiss -->
            <div class="fixed inset-0" @click="if (!isSubmitting) showConfirmModal = false"></div>

            <!-- Modal Dialog Content Box -->
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl z-10 border border-[#E5E9EF] transform transition-all"
                 @click.stop>
                
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-[#EAF0F9] text-[#0B3B70] flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-sans text-lg font-bold text-gray-900 m-0">Confirm Checkout</h3>
                        <p class="text-xs text-[#5B6472] mt-0.5">Please review before processing</p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="font-sans text-sm text-gray-700 leading-relaxed font-medium">
                        Confirm Checkout: Are you sure you want to process this transaction?
                    </p>

                    <div class="mt-4 p-3.5 bg-[#F8FAFC] rounded-xl border border-[#CBD5E1] space-y-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-[#64748B] font-semibold">Subtotal:</span>
                            <span class="font-bold text-gray-800" x-text="'₱' + calculateSubtotal().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        </div>
                        <template x-if="calculateDiscountAmount() > 0">
                            <div class="flex justify-between items-center text-[#B5504B]">
                                <span class="font-semibold" x-text="'Discount (' + discountType.charAt(0).toUpperCase() + discountType.slice(1) + '):'"></span>
                                <span class="font-bold" x-text="'-₱' + calculateDiscountAmount().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                            </div>
                        </template>
                        <div class="flex justify-between items-center border-t border-[#E2E8F0] pt-2">
                            <span class="text-[#64748B] font-semibold">Total Amount:</span>
                            <span class="font-sans text-lg font-black text-gray-900" x-text="'₱' + calculateTotal().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[#64748B] font-semibold">Items in Cart:</span>
                            <span class="font-sans font-bold text-gray-800" x-text="cart.reduce((sum, item) => sum + item.quantity, 0) + ' item(s)'"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-[#E5E9EF]">
                    <button type="button" 
                            @click="showConfirmModal = false" 
                            :disabled="isSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                            class="px-4 py-2.5 rounded-lg text-sm font-semibold border border-[#E5E9EF] bg-white text-gray-700 hover:bg-[#F4F6F9] transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" 
                            @click="confirmCheckout()" 
                            :disabled="isSubmitting"
                            :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                            class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#0B3B70] text-white hover:bg-[#082A52] transition-colors shadow-sm cursor-pointer inline-flex items-center gap-2">
                        <span x-show="!isSubmitting">Confirm &amp; Process</span>
                        <span x-show="isSubmitting" style="display: none;" class="inline-flex items-center gap-2">
                            <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Discount Details Modal -->
        <div x-show="showDiscountModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div @click.away="cancelDiscountDetails()" x-show="showDiscountModal" x-transition.opacity class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-[#E7EEF7] rounded-full mb-4">
                    <svg class="w-6 h-6 text-[#0B3B70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                </div>
                <h3 class="text-[17px] font-bold text-center text-gray-900 mb-6" x-text="discountType === 'senior' ? 'Senior Citizen Details' : 'PWD Details'"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[12px] font-bold text-[#334155] uppercase tracking-wider mb-1.5">Customer Name *</label>
                        <input type="text" x-model="discountRefName" class="w-full px-3 py-2 border border-[#CBD5E1] rounded-lg text-[13px] font-semibold bg-[#F8FAFC] focus:bg-white focus:ring-1 focus:ring-[#0B3B70]" placeholder="Enter Name">
                    </div>
                    <div>
                        <label class="block text-[12px] font-bold text-[#334155] uppercase tracking-wider mb-1.5">ID Number *</label>
                        <input type="text" x-model="discountRefId" class="w-full px-3 py-2 border border-[#CBD5E1] rounded-lg text-[13px] font-semibold bg-[#F8FAFC] focus:bg-white focus:ring-1 focus:ring-[#0B3B70]" placeholder="Enter ID Number">
                    </div>
                </div>
                <div class="flex gap-3 mt-7">
                    <button type="button" @click="cancelDiscountDetails()" class="flex-1 px-4 py-2.5 bg-[#F4F6F9] hover:bg-[#E5E9EF] text-[#1C2430] rounded-lg font-bold text-[13px] transition-colors">Cancel</button>
                    <button type="button" @click="applyDiscountDetails()" class="flex-1 px-4 py-2.5 bg-[#0B3B70] hover:bg-[#082A52] text-white rounded-lg font-bold text-[13px] transition-colors">Save & Apply</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posEngine() {
            return {
                customers: @json($customers),
                selectedCustomerId: '',
                isCompany: false,
                cart: [],
                discountType: 'none',
                discountValue: 0,
                discountRefName: '',
                discountRefId: '',
                showConfirmModal: false,
                showDiscountModal: false,
                isSubmitting: false,

                openCheckoutModal() {
                    if (this.cart.length === 0 || this.isSubmitting) return;
                    const form = document.getElementById('checkout-form');
                    if (form && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    this.showConfirmModal = true;
                },

                confirmCheckout() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    const form = document.getElementById('checkout-form');
                    if (form) {
                        form.submit();
                    }
                },

                checkCustomerType() {
                    const customer = this.customers.find(c => c.id == this.selectedCustomerId);
                    this.isCompany = customer ? (customer.customer_type === 'Coke (Residual)' || customer.customer_type === 'Company') : false;
                },

                addToCart(product) {
                    const isAccessory = product.is_accessory === true || product.standard_capacity_kg === null;
                    const isCylinder = !isAccessory;
                    // Coke (Residual) account LPG cylinders: add each cylinder as an independent line item with qty=1
                    const isCokeCylinder = this.isCompany && isCylinder;
                    const existingItem = isCokeCylinder ? null : this.cart.find(item => item.id === product.id);
                    
                    if (existingItem) {
                        existingItem.quantity++;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: parseFloat(product.price),
                            new_cylinder_price: product.new_cylinder_price !== null ? parseFloat(product.new_cylinder_price) : null,
                            standard_capacity_kg: product.standard_capacity_kg !== null ? parseFloat(product.standard_capacity_kg) : null,
                            is_accessory: isAccessory,
                            is_cylinder: isCylinder,
                            quantity: 1,
                            is_swap: isCylinder,
                            residual_kg: null
                        });
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                incrementQty(item) {
                    if (this.isCompany && item.standard_capacity_kg !== null && item.is_swap) return;
                    item.quantity++;
                },

                decrementQty(item) {
                    if (this.isCompany && item.standard_capacity_kg !== null && item.is_swap) return;
                    if (item.quantity > 1) {
                        item.quantity--;
                    }
                },

                getItemUnitPrice(item) {
                    if (item.is_cylinder) {
                        if (item.is_swap) {
                            return item.price;
                        }
                        return item.new_cylinder_price !== null ? item.new_cylinder_price : item.price;
                    }
                    return item.price;
                },

                getItemSubtotal(item) {
                    if (item.is_swap) {
                        if (this.isCompany && item.standard_capacity_kg !== null && item.residual_kg !== null && item.residual_kg !== '') {
                            const actualConsumed = Math.max(0, item.standard_capacity_kg - parseFloat(item.residual_kg || 0));
                            const pricePerKg = item.price / item.standard_capacity_kg;
                            return Math.round(actualConsumed * pricePerKg * item.quantity * 100) / 100;
                        }
                        return Math.round(item.price * item.quantity * 100) / 100;
                    }
                    const unitPrice = (item.new_cylinder_price !== null) ? item.new_cylinder_price : item.price;
                    return Math.round(unitPrice * item.quantity * 100) / 100;
                },

                calculateSubtotal() {
                    let total = 0;
                    this.cart.forEach(item => {
                        total += this.getItemSubtotal(item);
                    });
                    return Math.round(total * 100) / 100;
                },

                onDiscountTypeChange() {
                    if (this.discountType === 'none') {
                        this.discountValue = 0;
                    }
                    if (!['senior', 'pwd'].includes(this.discountType)) {
                        this.discountRefName = '';
                        this.discountRefId = '';
                    } else {
                        // Automatically pop up the modal when Senior/PWD is selected
                        this.showDiscountModal = true;
                    }
                },

                applyDiscountDetails() {
                    this.showDiscountModal = false;
                },

                cancelDiscountDetails() {
                    this.discountType = 'none';
                    this.discountValue = 0;
                    this.discountRefName = '';
                    this.discountRefId = '';
                    this.showDiscountModal = false;
                },

                calculateDiscountAmount() {
                    if (this.discountType === 'none') {
                        return 0;
                    }
                    const amount = parseFloat(this.discountValue);
                    return isNaN(amount) || amount < 0 ? 0 : amount;
                },

                calculateTotal() {
                    const subtotal = this.calculateSubtotal();
                    const discount = this.calculateDiscountAmount();
                    return Math.max(0, Math.round((subtotal - discount) * 100) / 100);
                }
            }
        }
    </script>
</x-app-layout>
