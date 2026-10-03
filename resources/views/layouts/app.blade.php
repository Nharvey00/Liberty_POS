<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Liberty LPG') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ sidebarOpen: false }" class="font-['Inter'] text-[#0F1D3A] antialiased bg-[#F1F5F9] m-0 flex min-h-screen text-[14px]">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition.opacity.duration.200ms
         @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 md:hidden" 
         style="display: none;"></div>

    <!-- Sidebar Navigation -->
    @include('layouts.navigation')

    <!-- Main Content Wrapper -->
    <div class="flex-1 min-w-0 flex flex-col">
        
        <!-- Page Content -->
        <main class="px-6 md:px-8 py-6 pb-16">
            @if (isset($header))
                <div class="flex items-center justify-between mb-6">
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

            {{ $slot }}
        </main>
    </div>

</body>
</html>