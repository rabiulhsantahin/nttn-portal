<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — NTTN Service Management Portal</title>
    <!-- Instant Theme Initializer to prevent flashing -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                var useDark = savedTheme ? (savedTheme === 'dark') : true;
                if (useDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Smooth transitions for theme switching */
        body, div, input, select, button {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .theme-toggle-btn {
            background-color: rgba(30, 41, 59, 0.85);
            border-color: rgba(51, 65, 85, 0.8);
            color: #cbd5e1;
            backdrop-filter: blur(8px);
        }
        .theme-toggle-btn:hover {
            background-color: rgba(51, 65, 85, 0.95);
            color: #ffffff;
        }

        /* Light Theme for Login */
        html:not(.dark) {
            color-scheme: light;
        }
        html:not(.dark) body {
            background-color: #f8fafc !important;
            background-image: radial-gradient(at 0% 0%, rgba(224, 231, 255, 0.6) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(207, 250, 254, 0.5) 0px, transparent 50%),
                              linear-gradient(135deg, #f8fafc 0%, #eef2f6 50%, #e2e8f0 100%) !important;
            color: #0f172a !important;
        }
        html:not(.dark) .login-card {
            background-color: rgba(255, 255, 255, 0.92) !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.08) !important;
        }
        html:not(.dark) .hero-panel {
            background-color: rgba(248, 250, 252, 0.85) !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) .stat-box {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }
        html:not(.dark) .border-slate-800 {
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) .text-white {
            color: #0f172a !important;
        }
        html:not(.dark) .text-slate-100 {
            color: #1e293b !important;
        }
        html:not(.dark) .text-slate-300 {
            color: #334155 !important;
        }
        html:not(.dark) .text-slate-400 {
            color: #64748b !important;
        }
        html:not(.dark) input[type="email"],
        html:not(.dark) input[type="password"],
        html:not(.dark) input[type="text"] {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html:not(.dark) input[type="email"]::placeholder,
        html:not(.dark) input[type="password"]::placeholder,
        html:not(.dark) input[type="text"]::placeholder {
            color: #94a3b8 !important;
        }
        html:not(.dark) input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        html:not(.dark) .theme-toggle-btn {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
        }
        html:not(.dark) .theme-toggle-btn:hover {
            background-color: #f8fafc !important;
        }
        html:not(.dark) .bg-slate-950\/60,
        html:not(.dark) .bg-slate-900\/60 {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) .bg-slate-900\/80 {
            background-color: #ffffff !important;
        }
        html:not(.dark) button[type="submit"] {
            color: #ffffff !important;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-950 via-[#0a1128] to-slate-950 p-4 sm:p-6 md:p-10 relative overflow-x-hidden selection:bg-blue-600 selection:text-white">

    <!-- Decorative background glow gradients -->
    <div class="fixed top-0 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <!-- Floating Theme Toggle -->
    <div class="fixed top-5 right-5 sm:top-6 sm:right-6 z-50">
        <button id="themeToggleBtn" type="button" onclick="toggleTheme()" 
            class="theme-toggle-btn flex items-center gap-2 px-3.5 py-2 rounded-2xl border text-xs font-semibold cursor-pointer shadow-lg transition-all duration-200"
            title="Switch between Light and Dark mode" aria-label="Toggle theme">
            <span class="theme-icon-sun hidden items-center gap-2 text-amber-400">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span class="font-mono text-slate-300">Light</span>
            </span>
            <span class="theme-icon-moon flex items-center gap-2 text-indigo-600">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="font-mono text-slate-700">Dark</span>
            </span>
        </button>
    </div>

    <!-- Main Container Card -->
    <div class="login-card w-full max-w-5xl grid lg:grid-cols-12 bg-slate-900/80 border border-slate-800 rounded-3xl overflow-hidden backdrop-blur-2xl shadow-2xl shadow-blue-950/60">
        
        <!-- Left Panel: Enterprise Branding & Telecom Infrastructure (7 cols) -->
        <div class="hero-panel lg:col-span-7 p-8 sm:p-10 md:p-12 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-slate-800 bg-gradient-to-br from-slate-900/90 via-slate-900/50 to-slate-950/80 relative">
            
            <div>
                <!-- Brand Header -->
                <div class="flex items-center gap-3.5 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-400 flex items-center justify-center font-bold text-white shadow-xl shadow-blue-500/25 ring-2 ring-white/10">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
                            <span>NTTN Portal</span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">v2026</span>
                        </div>
                        <div class="text-xs text-cyan-400 uppercase tracking-widest font-mono font-semibold">Transmission Network Platform</div>
                    </div>
                </div>

                <!-- Headline -->
                <div class="space-y-3 mb-8">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                        Nationwide Optical Infrastructure <span class="bg-gradient-to-r from-blue-400 via-cyan-300 to-indigo-300 bg-clip-text text-transparent">&amp; Operations</span>
                    </h1>
                    <p class="text-sm sm:text-base text-slate-400 leading-relaxed max-w-xl">
                        Unified B2B operations for optical fiber feasibility, multi-carrier transmission surveys, NOC diagnostics, SLA verification, and client billing.
                    </p>
                </div>

                <!-- Telecom Feature Grid -->
                <div class="grid sm:grid-cols-2 gap-3.5 mb-8">
                    <div class="stat-box p-4 rounded-2xl bg-slate-950/50 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white uppercase tracking-wider font-mono">Carrier Feasibility</div>
                            <div class="text-xs text-slate-400 mt-0.5 leading-snug">Fiber@Home &amp; Summit multi-path routing with GIS coordinates</div>
                        </div>
                    </div>

                    <div class="stat-box p-4 rounded-2xl bg-slate-950/50 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white uppercase tracking-wider font-mono">8-Point NOC SLA</div>
                            <div class="text-xs text-slate-400 mt-0.5 leading-snug">Optical attenuation, OTDR latency &amp; packet loss automated tests</div>
                        </div>
                    </div>

                    <div class="stat-box p-4 rounded-2xl bg-slate-950/50 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white uppercase tracking-wider font-mono">Optical Work Orders</div>
                            <div class="text-xs text-slate-400 mt-0.5 leading-snug">Real-time field splicing, duct access &amp; optical handovers</div>
                        </div>
                    </div>

                    <div class="stat-box p-4 rounded-2xl bg-slate-950/50 border border-slate-800/80 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white uppercase tracking-wider font-mono">Automated Tariffs</div>
                            <div class="text-xs text-slate-400 mt-0.5 leading-snug">Instant rate revision workflow, invoice ledgers &amp; commission approval</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Status Indicator -->
            <div class="pt-6 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-4 text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span class="text-slate-300 font-mono font-medium">Core Network Status:</span>
                    <span class="text-emerald-400 font-mono font-bold">ALL SYSTEMS OPERATIONAL</span>
                </div>
                <div class="text-slate-500 font-mono text-[11px]">
                    SLA Guarantee: 99.99% Uptime
                </div>
            </div>
        </div>

        <!-- Right Panel: Clean Secure Login Form (5 cols) -->
        <div class="lg:col-span-5 p-8 sm:p-10 md:p-12 flex flex-col justify-between bg-slate-900/90 relative">
            
            <div>
                <!-- Form Header -->
                <div class="mb-8">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 text-xs font-mono font-semibold mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Enterprise Authentication</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Portal Sign In</h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Enter your assigned corporate credentials to access your workspace.</p>
                </div>

                <!-- Alert Messages -->
                @if(session('info'))
                    <div class="mb-5 p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-300 text-xs flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ $error }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Credentials Form -->
                <form method="POST" action="{{ route('login.post') }}" class="space-y-5" id="loginForm">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Corporate Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                            </div>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                placeholder="name@organization.com" autocomplete="username"
                                class="w-full pl-10 pr-4 py-3 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                Account Password
                            </label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                placeholder="Enter your password" autocomplete="current-password"
                                class="w-full pl-10 pr-12 py-3 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                            <button type="button" onclick="togglePasswordVisibility()" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition-colors cursor-pointer" 
                                title="Toggle password visibility" aria-label="Toggle password visibility">
                                <svg id="eyeOpenIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeClosedIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-blue-600 focus:ring-0 focus:ring-offset-0">
                            <span>Keep me logged in</span>
                        </label>
                        <span class="text-slate-500 font-mono text-[11px]">Role Auth Required</span>
                    </div>

                    <button type="submit" id="submitBtn" class="w-full py-3.5 px-5 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-sm shadow-xl shadow-blue-600/30 hover:shadow-blue-600/50 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                        <span>Sign In to Portal</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Footer Security Protocol -->
            <div class="pt-8 mt-6 border-t border-slate-800/80 space-y-2">
                <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>256-Bit TLS Encryption &bull; RBAC Protected</span>
                </div>
                <div class="text-[10px] text-slate-500">
                    Access is restricted to authorized telecom personnel, licensed resellers, and network administrators. All activity is logged and audited.
                </div>
            </div>

        </div>

    </div>

    <!-- Interactive Scripts -->
    <script>
        function togglePasswordVisibility() {
            var pwdInput = document.getElementById('password');
            var eyeOpen = document.getElementById('eyeOpenIcon');
            var eyeClosed = document.getElementById('eyeClosedIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                pwdInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }

        function updateThemeUI(isDark) {
            var sunIcons = document.querySelectorAll('.theme-icon-sun');
            var moonIcons = document.querySelectorAll('.theme-icon-moon');
            var toggleBtns = document.querySelectorAll('.theme-toggle-btn');
            
            sunIcons.forEach(function(el) {
                if (isDark) {
                    el.classList.remove('hidden');
                    el.classList.add('flex');
                } else {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });
            
            moonIcons.forEach(function(el) {
                if (isDark) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                } else {
                    el.classList.remove('hidden');
                    el.classList.add('flex');
                }
            });

            toggleBtns.forEach(function(btn) {
                if (isDark) {
                    btn.classList.add('bg-slate-800/90', 'border-slate-700/80', 'hover:bg-slate-700/60');
                    btn.classList.remove('bg-white', 'border-slate-200', 'hover:bg-slate-50', 'shadow-sm');
                } else {
                    btn.classList.remove('bg-slate-800/90', 'border-slate-700/80', 'hover:bg-slate-700/60');
                    btn.classList.add('bg-white', 'border-slate-200', 'hover:bg-slate-50', 'shadow-sm');
                }
            });
        }

        function toggleTheme() {
            var isDark = document.documentElement.classList.contains('dark');
            var nextTheme = isDark ? 'light' : 'dark';
            if (nextTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
            try {
                localStorage.setItem('theme', nextTheme);
            } catch(e) {}
            updateThemeUI(nextTheme === 'dark');
        }

        // Initialize UI on load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                updateThemeUI(document.documentElement.classList.contains('dark'));
            });
        } else {
            updateThemeUI(document.documentElement.classList.contains('dark'));
        }
    </script>
</body>
</html>
