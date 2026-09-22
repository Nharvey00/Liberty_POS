<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Edit Staff Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $user->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('users.update', $user) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2">
                <label for="name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Staff Full Name <span class="text-[#B5504B]">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="email" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Email Address / Login ID <span class="text-[#B5504B]">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('email') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="role_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">System Access Role <span class="text-[#B5504B]">*</span></label>
                <select name="role_id" id="role_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 pt-3 border-t border-[#E5E9EF] mt-2">
                <h4 class="text-[13px] font-bold text-[#1C2430] mb-1">Reset Password (Optional)</h4>
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="password" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">New Password</label>
                <input type="password" name="password" id="password" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('password') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="password_confirmation" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('users.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Staff Account</button>
        </div>
    </form>
</x-app-layout>