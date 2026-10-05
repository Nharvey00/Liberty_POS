<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Edit Staff Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $user->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('users.update', $user) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2 grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_100px] gap-3">
                <div>
                    <label for="first_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">First Name <span class="text-[#B5504B]">*</span></label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('first_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="middle_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('middle_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Last Name <span class="text-[#B5504B]">*</span></label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('last_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="suffix" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Suffix</label>
                    <input type="text" name="suffix" id="suffix" placeholder="Jr., III" value="{{ old('suffix', $user->suffix) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('suffix') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-span-2">
                <label for="email" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Email Address / Login ID <span class="text-[#B5504B]">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('email') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="role_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">System Access Role <span class="text-[#B5504B]">*</span></label>
                <select name="role_id" id="role_id" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 pt-3 border-t border-[#E5E9EF] mt-2">
                <h4 class="font-sans text-[13px] font-bold text-gray-900 mb-1">Reset Password (Optional)</h4>
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

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('users.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Staff Account</button>
        </div>
    </form>
</x-app-layout>