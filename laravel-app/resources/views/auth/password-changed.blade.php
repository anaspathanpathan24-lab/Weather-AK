<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(31,41,55,0.95),_rgba(15,23,42,1))] text-white flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white/10 p-8 text-center shadow-2xl backdrop-blur-xl">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-400/20 text-3xl">✅</div>
        <h1 class="text-3xl font-semibold">Password Changed Successfully</h1>
        <p class="mt-3 text-sm text-slate-300">Your password has been updated successfully. You can now log in with your new credentials.</p>
        <a href="{{ url('/login') }}" class="mt-6 inline-block rounded-2xl bg-cyan-400 px-5 py-3 font-semibold text-slate-900 transition hover:bg-cyan-300">Go to Login</a>
    </div>
</body>
</html>
