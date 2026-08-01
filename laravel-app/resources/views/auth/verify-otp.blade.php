<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(31,41,55,0.95),_rgba(15,23,42,1))] text-white flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-400/20 text-2xl">✉️</div>
            <h1 class="text-3xl font-semibold">Verify OTP</h1>
            <p class="mt-2 text-sm text-slate-300">Enter the 6-digit code sent to <span class="font-medium text-cyan-300">{{ $email }}</span>.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-400/40 bg-red-500/20 px-4 py-3 text-sm text-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-400/40 bg-emerald-500/20 px-4 py-3 text-sm text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.verify') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="otp">6 Digit OTP</label>
                <input id="otp" name="otp" type="text" maxlength="6" required class="w-full rounded-2xl border border-white/20 bg-slate-900/60 px-4 py-3 text-center text-2xl tracking-[0.4em] text-white outline-none focus:border-cyan-400" placeholder="483291">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-cyan-400 px-4 py-3 font-semibold text-slate-900 transition hover:bg-cyan-300">Verify</button>
        </form>

        <div class="mt-6 text-center text-sm text-slate-300">
            <a href="{{ route('password.request') }}" class="text-cyan-300 hover:underline">Resend OTP</a>
        </div>
    </div>
</body>
</html>
