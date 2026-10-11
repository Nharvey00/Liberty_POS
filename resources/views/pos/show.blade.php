<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0 print:hidden">Transaction Receipt</h1>
    </x-slot>

    <div class="flex justify-center py-6 print:py-0 print:m-0 print:block print:w-full">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-8 w-full max-w-[400px] text-[12.5px] text-[#1C2430] print:w-[80mm] print:mx-auto print:p-2 print:text-black print:shadow-none print:border-none print:bg-white print:max-w-none print:m-0">
            <div class="print:px-2">
                <div class="text-center font-sans font-bold text-[18px] text-gray-900 mb-1 print:text-base print:font-black print:text-black">LIBERTY LPG CENTER</div>
                <div class="text-center text-[11px] text-[#5B6472] mb-2 leading-tight print:text-[10px] print:font-medium print:text-black">
                    Owned by: Jose Maria Tionko<br>
                    Lot C Blk, 11 Doña Socorro, Vicenta Village, Davao City, 8000 Davao del Sur<br>
                    TIN Number: 
                </div>
                <div class="text-center text-[#5B6472] mb-6 print:text-xs print:font-bold print:text-black print:mb-3">Official Receipt</div>
                
                <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1 print:border-black print:border-dashed print:text-xs print:font-medium print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Date</span>
                    <span class="font-bold print:text-black">{{ $order->created_at->format('M d, Y, h:i A') }}</span>
                </div>
                @if($order->invoice_number)
                <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1 print:border-black print:border-dashed print:text-xs print:font-medium print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Invoice No.</span>
                    <span class="font-bold print:text-black">{{ $order->invoice_number }}</span>
                </div>
                @endif
                <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1 print:border-black print:border-dashed print:text-xs print:font-medium print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Order No.</span>
                    <span class="font-bold print:text-black">OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1 print:border-black print:border-dashed print:text-xs print:font-medium print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Cashier</span>
                    <span class="font-bold print:text-black">{{ $order->user->name }}</span>
                </div>
                <div class="flex justify-between py-1 mb-4 print:mb-2 print:text-xs print:font-medium print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Customer</span>
                    <span class="font-bold text-right print:text-black">
                        {{ $order->customer->name ?? 'Walk-in Customer' }}
                        @if($order->customer && $order->customer->business_name)<br>({{ $order->customer->business_name }})@endif
                    </span>
                </div>

                <div class="border-t border-dashed border-[#1C2430] my-4 print:my-2 print:border-black print:border-dashed"></div>

                <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0 print:overflow-visible print:mx-0 print:px-0">
                    <table class="w-full text-[12.5px] mb-4 border-b border-dashed border-[#E5E9EF] pb-4 print:mb-2 print:pb-2 print:text-xs print:border-black print:border-dashed">
                        <thead>
                            <tr class="border-b border-[#E5E9EF] text-[#5B6472] print:border-black print:border-dashed print:text-black print:font-bold">
                                <th class="text-left py-2 print:py-1 font-semibold whitespace-nowrap print:font-bold print:text-black">Item</th>
                                <th class="text-center py-2 print:py-1 font-semibold whitespace-nowrap print:font-bold print:text-black">Qty</th>
                                <th class="text-right py-2 print:py-1 font-semibold whitespace-nowrap print:font-bold print:text-black">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#1C2430] print:text-black">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="py-2.5 print:py-1 border-b border-[#F4F6F9] print:border-black/30">
                                        <span class="font-bold print:font-bold print:text-black">{{ $item->product->name }}</span>
                                        @if($item->product->new_cylinder_price !== null && !$item->is_swap)
                                            <br><span class="text-[11px] italic text-[#5B6472] print:text-[10px] print:not-italic print:font-semibold print:text-black">(New Cylinder Purchased)</span>
                                        @endif
                                        @if($item->residual_kg !== null)
                                            <br><span class="text-[11px] font-bold text-[#B4700A] print:text-[10px] print:font-bold print:text-black">
                                                Res: {{ $item->residual_kg }}kg | Consumed: {{ $item->actual_consumed_kg }}kg
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center align-top py-2.5 print:py-1 border-b border-[#F4F6F9] print:border-black/30 font-semibold print:font-bold print:text-black">{{ $item->quantity }}</td>
                                    <td class="text-right align-top py-2.5 print:py-1 border-b border-[#F4F6F9] print:border-black/30 font-bold print:font-bold print:text-black">₱{{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-[13px] border-b border-dashed border-[#E5E9EF] pb-4 mb-4 space-y-1.5 print:pb-2 print:mb-2 print:space-y-1 print:text-xs print:border-black print:border-dashed">
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-[#5B6472] font-semibold print:font-medium print:text-black">
                            <span>Subtotal:</span>
                            <span class="print:font-bold">₱{{ number_format($order->total_amount + $order->discount_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-[#B5504B] font-bold print:text-black">
                            <span>
                                Less Discount
                                @if($order->discount_type)
                                    ({{ ucfirst($order->discount_type) }})
                                @endif
                                :
                            </span>
                            <span>- ₱{{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                        @if($order->discount_reference_name || $order->discount_reference_id)
                            <div class="text-[#5B6472] text-[11px] mt-1 print:text-black print:font-medium leading-tight">
                                @if($order->discount_reference_name)<div>Ref Name: <span class="print:font-bold">{{ $order->discount_reference_name }}</span></div>@endif
                                @if($order->discount_reference_id)<div>ID No.: <span class="print:font-bold">{{ $order->discount_reference_id }}</span></div>@endif
                            </div>
                        @elseif($order->discount_type === 'senior' && $order->senior_id)
                            <div class="text-[#5B6472] text-[11px] mt-1 print:text-black print:font-medium">Senior ID: <span class="print:font-bold">{{ $order->senior_id }}</span></div>
                        @endif
                    @endif
                    <div class="flex justify-between font-sans font-bold text-[16px] text-gray-900 mt-3 pt-3 border-t border-[#E5E9EF] print:border-black print:border-dashed print:text-sm print:font-black print:text-black print:mt-2 print:pt-2">
                        <span>Total Due:</span>
                        <span>₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>

                    <div class="mt-3 pt-3 border-t border-dashed border-[#E5E9EF] print:border-black print:border-dashed print:mt-2 print:pt-2">
                        <div class="flex justify-between text-[#5B6472] text-[11.5px] mb-1 print:text-[10px] print:text-black print:font-medium">
                            <span>Vatable Sale:</span>
                            <span class="print:font-bold">₱{{ number_format($order->total_amount / 1.12, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-[#5B6472] text-[11.5px] mb-1 print:text-[10px] print:text-black print:font-medium">
                            <span>VAT (12%):</span>
                            <span class="print:font-bold">₱{{ number_format($order->total_amount - ($order->total_amount / 1.12), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between py-1 mt-2 print:mt-1 print:text-xs print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Payment Method</span>
                    <span class="font-bold uppercase print:text-black">{{ $order->payment_method }}</span>
                </div>
                <div class="flex justify-between py-1 print:text-xs print:text-black">
                    <span class="font-semibold text-[#5B6472] print:text-black print:font-bold">Status</span>
                    @if($order->payment_method === 'Credit')
                        <span class="text-[#B4700A] font-bold print:text-black">Added to Ledger</span>
                    @else
                        <span class="text-[#1E8E5A] font-bold print:text-black">Completed</span>
                    @endif
                </div>

                <div class="text-center text-[11px] text-[#5B6472] mt-8 pt-4 border-t border-dashed border-[#E5E9EF] print:border-black print:border-dashed print:text-[10px] print:font-medium print:text-black print:mt-3 print:pt-2">
                    <p>Thank you for choosing Liberty LPG!</p>
                    <p>Please keep this receipt for your records.</p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-between gap-3 print:hidden">
                <a href="{{ route('pos.create') }}" class="flex-1 text-center rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9] print:hidden">Back to POS</a>
                <button onclick="window.print()" class="flex-1 rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] print:hidden">🖨 Print</button>
            </div>
        </div>
    </div>

    <style>
        @media print {
            @page { margin: 5mm; }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white;
                margin: 0;
                padding: 0;
            }
            .print\:hidden { display: none !important; }
        }
    </style>
</x-app-layout>