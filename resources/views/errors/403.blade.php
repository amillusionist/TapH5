<!DOCTYPE html>
<html lang="en" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 — Access Denied | TapH5 Games</title>
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#090d16">

        <!-- Fonts -->
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet" />

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        },
                    },
                },
            };
        </script>
    </head>
    <body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">
        <!-- Top Navigation Bar -->
        <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md px-6 py-4">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-amber-500 to-rose-400 flex items-center justify-center text-slate-950 font-black text-sm shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform">
                        T5
                    </div>
                    <div>
                        <span class="font-extrabold text-white text-base tracking-tight">TAPH5</span>
                        <span class="font-bold text-amber-400 text-xs ml-1">GAMES</span>
                    </div>
                </a>
                <a href="/" class="text-xs font-semibold text-slate-300 hover:text-white transition-colors">
                    Back to Portal &rarr;
                </a>
            </div>
        </header>

        <!-- Main Hero 403 Section -->
        <main class="flex-1 flex items-center justify-center p-6 text-center">
            <div class="max-w-lg mx-auto space-y-6">
                <!-- 403 badge -->
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl relative">
                    <div class="absolute inset-0 bg-amber-500/10 rounded-3xl animate-pulse"></div>
                    <span class="text-4xl font-black text-amber-400 tracking-tighter">403</span>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Restricted Area — Access Denied
                    </h1>
                    <p class="text-sm text-slate-400 max-w-md mx-auto leading-relaxed">
                        You do not possess the required administrator credentials or authorization role to access this restricted management console.
                    </p>
                </div>

                <!-- Action CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <a
                        href="/"
                        class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 hover:from-emerald-400 hover:to-teal-300 text-slate-950 text-xs font-bold uppercase tracking-wider transition-all shadow-lg shadow-emerald-500/25 transform hover:-translate-y-0.5"
                    >
                        Return to Public Portal
                    </a>
                    <a
                        href="/login"
                        class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-colors border border-slate-700"
                    >
                        Account Sign In
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 py-6 text-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} TapH5 Games. Fast, free HTML5 gaming in your browser.</p>
        </footer>
    </body>
</html>

