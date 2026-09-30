<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Void Order</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Are you sure you want to void this transaction?</div>
    </x-slot>

    <div class="max-w-2xl mx-auto py-6">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 shadow-sm">
            <h2 class="text-[16px] font-bold text-[#1C2430] mb-4">Order Details</h2>
            
            <div class="space-y-2 text-[13px] text-[#1C2430] mb-6 border-b border-[#E5E9EF] pb-4">
                <div class="flex justify-between">
                    <span class="text-[#5B6472] font-semibold">Order ID:</span>
                    <span class="font-bold">OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#5B6472] font-semibold">Date:</span>
                    <span>{{ $order->created_at->format('M d, Y - h:i A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#5B6472] font-semibold">Customer:</span>
                    <span>{{ $order->customer->name ?? 'Walk-in Customer' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#5B6472] font-semibold">Total Amount:</span>
                    <span class="font-bold text-[#B5504B]">₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-[13px] font-semibold text-[#5B6472] mb-2 uppercase tracking-[0.02em]">Items:</h3>
                <ul class="text-[13px] text-[#1C2430] space-y-1 list-disc pl-4">
                    @foreach($order->items as $item)
                        <li>{{ $item->quantity }}x {{ $item->product->name }} - ₱{{ number_format($item->subtotal, 2) }}</li>
                    @endforeach
                </ul>
            </div>

            <form action="{{ url('orders/' . $order->id . '/void') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="void_reason" class="block text-[13px] font-semibold text-[#5B6472] mb-1">Reason for Voiding <span class="text-[#B5504B]">*</span></label>
                    <textarea name="void_reason" id="void_reason" rows="3" required minlength="5" class="w-full border border-[#E5E9EF] rounded-lg text-[13px] focus:border-[#0B3B70] focus:ring focus:ring-[#0B3B70] focus:ring-opacity-50 px-3 py-2" placeholder="Please provide a detailed reason..."></textarea>
                    @error('void_reason')
                        <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-[#E5E9EF]">
                    <a href="{{ route('orders.show', $order->id) }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9] transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e] transition-colors">
                        Confirm Void
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
