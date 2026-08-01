<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(31,41,55,0.95),_rgba(15,23,42,1))] text-white flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-400/20 text-2xl">🔒</div>
            <h1 class="text-3xl font-semibold">Reset Password</h1>
            <p class="mt-2 text-sm text-slate-300">Create a new strong password for <span class="font-medium text-cyan-300">{{ $email }}</span>.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-400/40 bg-red-500/20 px-4 py-3 text-sm text-red-100">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="password">New Password</label>
                <input id="password" name="password" type="password" required class="w-full rounded-2xl border border-white/20 bg-slate-900/60 px-4 py-3 text-white outline-none focus:border-cyan-400">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-2xl border border-white/20 bg-slate-900/60 px-4 py-3 text-white outline-none focus:border-cyan-400">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-emerald-400 px-4 py-3 font-semibold text-slate-900 transition hover:bg-emerald-300">Update Password</button>
        </form>
    </div>
</body>
</html>
