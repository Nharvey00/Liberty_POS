<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Liberty LPG</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-['Inter'] text-[#1C2430] antialiased min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_20%_15%,#0F4783_0%,#082A52_55%,#05192F_100%)] p-5">

    <div class="bg-white rounded-[20px] px-9 py-10 w-full max-w-[440px] shadow-[0_30px_70px_rgba(5,20,40,0.35)]">
        <!-- Brand Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#0B3B70] to-[#5D89B0] flex items-center justify-center font-['Manrope'] font-extrabold text-[15px] text-white shrink-0">
                LG
            </div>
            <div>
                <div class="font-['Manrope'] text-[19px] font-extrabold leading-tight">Liberty LPG Center</div>
                <div class="text-[12px] text-[#5B6472] mt-[1px]">Sales & Inventory System</div>
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5 uppercase tracking-wide">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-[#B5504B] text-xs" />
            </div>

            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5 uppercase tracking-wide">Password</label>
                <input id="password" type="password" name="password" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-[#B5504B] text-xs" />
            </div>

            <!-- Remember Me -->
            <div class="block mb-6">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-[#E5E9EF] text-[#0B3B70] shadow-sm focus:ring-[#0B3B70]" name="remember">
                    <span class="ms-2 text-[13px] text-[#5B6472]">Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 border-none rounded-lg bg-[#0B3B70] text-white font-bold text-[14px] hover:bg-[#082A52] transition-colors">
                Log In
            </button>
            
            <div class="text-[11px] text-[#5B6472] text-center mt-4">
                Secure access is restricted to authorized personnel.
            </div>
        </form>
    </div>

</body>
</html>