<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0 print:hidden">Transaction Receipt</h1>
    </x-slot>

    <div class="flex justify-center py-6 print:py-0">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-8 w-full max-w-[400px] text-[12.5px] text-[#1C2430] print:border-none print:shadow-none print:p-0 print:max-w-full">
            
            <div class="text-center font-['Manrope'] font-extrabold text-[18px] mb-1">LIBERTY LPG CENTER</div>
            <div class="text-center text-[#5B6472] mb-6">Official Receipt</div>
            
            <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1">
                <span class="font-semibold text-[#5B6472]">Date</span>
                <span class="font-bold">{{ $order->created_at->format('M d, Y, h:i A') }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1">
                <span class="font-semibold text-[#5B6472]">Order No.</span>
                <span class="font-bold">OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-dashed border-[#E5E9EF] mb-1">
                <span class="font-semibold text-[#5B6472]">Cashier</span>
                <span class="font-bold">{{ $order->user->name }}</span>
            </div>
            <div class="flex justify-between py-1 mb-4">
                <span class="font-semibold text-[#5B6472]">Customer</span>
                <span class="font-bold text-right">
                    {{ $order->customer->name ?? 'Walk-in Customer' }}
                    @if($order->customer && $order->customer->business_name)<br>({{ $order->customer->business_name }})@endif
                </span>
            </div>

            <div class="border-t border-dashed border-[#1C2430] my-4"></div>

            <table class="w-full text-[12.5px] mb-4 border-b border-dashed border-[#E5E9EF] pb-4">
                <thead>
                    <tr class="border-b border-[#E5E9EF] text-[#5B6472]">
                        <th class="text-left py-2 font-semibold">Item</th>
                        <th class="text-center py-2 font-semibold">Qty</th>
                        <th class="text-right py-2 font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-[#1C2430]">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-2.5 border-b border-[#F4F6F9]">
                                <span class="font-bold">{{ $item->product->name }}</span>
                                @if($item->product->new_cylinder_price !== null && !$item->is_swap)
                                    <br><span class="text-[11px] italic text-[#5B6472]">(New Cylinder Purchased)</span>
                                @endif
                                @if($item->residual_kg !== null)
                                    <br><span class="text-[11px] font-bold text-[#B4700A]">
                                        Res: {{ $item->residual_kg }}kg | Consumed: {{ $item->actual_consumed_kg }}kg
                                    </span>
                                @endif
                            </td>
                            <td class="text-center align-top py-2.5 border-b border-[#F4F6F9] font-semibold">{{ $item->quantity }}</td>
                            <td class="text-right align-top py-2.5 border-b border-[#F4F6F9] font-bold">₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="text-[13px] border-b border-dashed border-[#E5E9EF] pb-4 mb-4 space-y-1.5">
                @if($order->discount_amount > 0)
                    <div class="flex justify-between text-[#5B6472] font-semibold">
                        <span>Subtotal:</span>
                        <span>₱{{ number_format($order->total_amount + $order->discount_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-[#B5504B] font-bold">
                        <span>Less Discount:</span>
                        <span>- ₱{{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-['Manrope'] font-extrabold text-[16px] text-[#1C2430] mt-3 pt-3 border-t border-[#E5E9EF]">
                    <span>Total Due:</span>
                    <span>₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="flex justify-between py-1 mt-2">
                <span class="font-semibold text-[#5B6472]">Payment Method</span>
                <span class="font-bold uppercase">{{ $order->payment_method }}</span>
            </div>
            <div class="flex justify-between py-1">
                <span class="font-semibold text-[#5B6472]">Status</span>
                @if($order->payment_method === 'Credit')
                    <span class="text-[#B4700A] font-bold">Added to Ledger</span>
                @else
                    <span class="text-[#1E8E5A] font-bold">Completed</span>
                @endif
            </div>

            <div class="text-center text-[11px] text-[#5B6472] mt-8 pt-4 border-t border-dashed border-[#E5E9EF]">
                <p>Thank you for choosing Liberty LPG!</p>
                <p>Please keep this receipt for your records.</p>
            </div>
            
            <div class="mt-6 flex justify-between gap-3 print:hidden">
                <a href="{{ route('pos.create') }}" class="flex-1 text-center rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Back to POS</a>
                <button onclick="window.print()" class="flex-1 rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">🖨 Print</button>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .print\:hidden { display: none !important; }
        }
    </style>
</x-app-layout>