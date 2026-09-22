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
<body class="font-['Inter'] text-[#1C2430] antialiased bg-[#F4F6F9] m-0 flex min-h-screen text-[14px]">

    <!-- Sidebar Navigation -->
    @include('layouts.navigation')

    <!-- Main Content Wrapper -->
    <div class="flex-1 min-w-0 flex flex-col">
        
        <!-- Topbar -->
        @if (isset($header))
            <div class="flex items-center justify-between px-7 py-4 bg-white border-b border-[#E5E9EF] sticky top-0 z-10">
                <div>
                    {{ $header }}
                </div>
            </div>
        @endif

        <!-- Page Content -->
        <main class="px-7 py-6 pb-16">
            {{ $slot }}
        </main>
    </div>

</body>
</html>