<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Liberty LPG Center') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            @page { margin: 5mm; }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                display: block !important;
            }
            nav, aside, header button, .print\:hidden, .no-print {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body x-data="{ sidebarOpen: false }" class="font-sans text-[#0F1D3A] antialiased bg-[#F1F5F9] m-0 flex min-h-screen text-[14px]">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition.opacity.duration.200ms
         @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 md:hidden print:hidden" 
         style="display: none;"></div>

    <!-- Sidebar Navigation -->
    @include('layouts.navigation')

    <!-- Main Content Wrapper -->
    <div class="flex-1 min-w-0 flex flex-col print:w-full print:p-0 print:m-0">
        
        <!-- Page Content -->
        <main class="px-6 md:px-8 py-6 pb-16 print:p-0 print:m-0 print:w-full">
            @if (isset($header))
                <div class="flex items-center justify-between mb-6 print:hidden">
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

    <script>
        function liveSearch(paramName = 'search') {
            return {
                query: '',
                paramName: paramName,
                init() {
                    const urlParams = new URLSearchParams(window.location.search);
                    this.query = urlParams.get(this.paramName) || '';
                },
                async performSearch() {
                    const form = this.$refs.form;
                    if (!form) return;
                    
                    const url = new URL(form.action);
                    const currentParams = new URLSearchParams(window.location.search);
                    currentParams.forEach((val, key) => url.searchParams.set(key, val));
                    url.searchParams.set(this.paramName, this.query);
                    url.searchParams.delete('page'); // Reset to page 1 on new search

                    try {
                        const res = await fetch(url.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const html = await res.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        const newContainer = doc.getElementById('table-container');
                        if (newContainer) {
                            document.getElementById('table-container').innerHTML = newContainer.innerHTML;
                        }

                        // Update URL silently
                        window.history.pushState({}, '', url.toString());
                    } catch (e) {
                        console.error('Live search failed:', e);
                    }
                }
            }
        }
    </script>
</body>
</html>