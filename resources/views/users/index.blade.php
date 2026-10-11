<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">User Accounts &amp; Staffing</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manage system access levels, roles, and staff permissions</div>
    </x-slot>
    <div x-data="{ showDeleteModal: false, deleteAction: '' }">

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="liveSearch()" class="flex flex-col gap-4">
        <div class="flex items-center justify-between mb-1 gap-3 flex-wrap">
            <form x-ref="form" method="GET" action="{{ route('users.index') }}" @submit.prevent="performSearch" class="flex-1 min-w-[200px] max-w-[300px] flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search staff member..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
            </form>
            <div class="flex gap-2.5 flex-wrap items-center">
                <a href="{{ route('users.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ Add Staff Account</a>
            </div>
        </div>

        <div id="table-container">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
                    <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Name</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Email / Username</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Access Level</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">{{ $user->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $user->email }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E7EEF7] text-[#0B3B70]">
                                    {{ $user->role->role_name ?? 'Staff · Level 1' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Active</span>
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('users.edit', $user) }}" class="text-[#5D89B0] font-bold text-[12px] hover:underline">Edit</a>
                                    @if(Auth::id() !== $user->id)
                                        <button type="button" @click="deleteAction = '{{ route('users.destroy', $user) }}'; showDeleteModal = true" class="px-2.5 py-1 text-[11.5px] font-semibold text-[#B5504B] bg-[#F7E9E8] hover:bg-[#F0D5D3] rounded-md transition-colors cursor-pointer border border-[#B5504B]/20">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[13px] text-[#5B6472]">No staff accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $users->links() }}
        </div>
    </div>
    </div> <!-- Close table-container -->
    </div> <!-- Close liveSearch -->

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div @click.away="showDeleteModal = false" x-show="showDeleteModal" x-transition.opacity class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-[#F7E9E8] rounded-full mb-4">
                <svg class="w-6 h-6 text-[#B5504B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-[17px] font-bold text-center text-gray-900 mb-2">Are you sure?</h3>
            <p class="text-[13px] text-center text-[#5B6472] mb-6 leading-relaxed">This action cannot be undone. This record will be safely removed from active views.</p>
            <div class="flex gap-3 justify-center">
                <button type="button" @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-[#F4F6F9] hover:bg-[#E5E9EF] text-[#1C2430] rounded-lg font-bold text-[13px] transition-colors">Cancel</button>
                <form :action="deleteAction" method="POST" class="flex-1 flex m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2.5 bg-[#B5504B] hover:bg-[#9c423e] text-white rounded-lg font-bold text-[13px] transition-colors">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>