<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired - Liberty POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="min-h-screen flex flex-col justify-center items-center px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-200 text-center">
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black font-sans text-gray-900 tracking-tight mb-2">419 - Page Expired</h1>
            <p class="text-sm text-gray-600 mb-6 font-medium">Session expired. Please refresh the page.</p>
            <div class="flex flex-col gap-2">
                <button onclick="window.location.reload()" class="w-full py-2.5 px-4 bg-[#0B3B70] text-white font-bold rounded-lg hover:bg-[#082A52] transition-colors cursor-pointer text-sm">
                    Refresh Page
                </button>
                <a href="{{ url('/') }}" class="w-full py-2.5 px-4 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors text-sm">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
