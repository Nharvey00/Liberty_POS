<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Liberty LPG') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ sidebarOpen: false }" class="font-['Inter'] text-[#1C2430] antialiased bg-[#F4F6F9] m-0 flex min-h-screen text-[14px]">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 md:hidden" 
         style="display: none;"></div>

    <!-- Sidebar Navigation -->
    @include('layouts.navigation')

    <!-- Main Content Wrapper -->
    <div class="flex-1 min-w-0 flex flex-col">
        
        <!-- Topbar -->
        @if (isset($header))
            <div class="flex items-center justify-between px-4 md:px-7 py-4 bg-white border-b border-[#E5E9EF] sticky top-0 z-10">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <button @click="sidebarOpen = true" type="button" class="md:hidden text-[#5B6472] hover:text-[#0B3B70] p-1.5 -ml-1 rounded-lg focus:outline-none shrink-0" aria-label="Open Sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="flex-1 min-w-0">
                        {{ $header }}
                    </div>
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <main class="px-4 md:px-7 py-6 pb-16">
            {{ $slot }}
        </main>
    </div>

</body>
</html>