<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CCF Multi-Purpose Cooperative</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: url('/images/login-bg.png'); background-color: #052e16;">
    <div class="flex min-h-screen w-full items-center justify-center px-4 py-8" style="background-color: rgba(5, 46, 22, 0.7);">
        <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-3xl text-white shadow-lg shadow-brand-600/30">
                <i class="fa fa-leaf"></i>
            </div>
            <h1 class="text-2xl font-bold text-white" style="text-shadow: 0 2px 8px rgba(0,0,0,0.5);">CCFMPC</h1>
            <p class="mt-1 text-sm text-white" style="text-shadow: 0 1px 4px rgba(0,0,0,0.5);">Point of Sale System</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-xl">
            @if ($errors->has('email'))
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <i class="fa fa-exclamation-circle mt-0.5"></i>
                    <div>{{ $errors->first('email') }}</div>
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
                    Sign In
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-white" style="text-shadow: 0 1px 4px rgba(0,0,0,0.5);">
            Cavite College of Fisheries Multi-Purpose Cooperative
        </p>
    </div>
    </div>
</body>
</html>
