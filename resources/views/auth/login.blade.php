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
            background-color: #f1f5f9 !important;
            background-image: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%) !important;
            color: #0f172a !important;
        }
        html:not(.dark) .grid.md\:grid-cols-5 {
            background-color: rgba(255, 255, 255, 0.95) !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08) !important;
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
        html:not(.dark) input[type="password"] {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html:not(.dark) input[type="email"]::placeholder,
        html:not(.dark) input[type="password"]::placeholder {
            color: #94a3b8 !important;
        }
        html:not(.dark) input[type="email"]:focus,
        html:not(.dark) input[type="password"]:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
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
        html:not(.dark) .cursor-pointer.p-3\.5 {
            background-color: #f8fafc !important;
            background-image: none !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }
        html:not(.dark) .cursor-pointer.p-3\.5:hover {
            background-color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08) !important;
        }
        html:not(.dark) .bg-slate-950\/60 {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) button[type="submit"] {
            color: #ffffff !important;
        }
    </style>
</head>
<body class="min-h-full flex items-center justify-center bg-gradient-to-br from-slate-950 via-[#0b1329] to-slate-950 p-6 relative">
    
    <!-- Floating Theme Toggle -->
    <div class="fixed top-6 right-6 z-50">
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

    <div class="w-full max-w-4xl grid md:grid-cols-5 gap-8 bg-slate-900/80 border border-slate-800 rounded-3xl p-8 backdrop-blur-xl shadow-2xl shadow-blue-950/50">
        
        <!-- Left: Branding & Direct Form -->
        <div class="md:col-span-2 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800 pb-8 md:pb-0 md:pr-8">
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-400 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/25">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl font-extrabold text-white">NTTN Portal</div>
                        <div class="text-xs text-cyan-400 uppercase tracking-widest font-mono">B2B Core System</div>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-white tracking-tight mb-2">Welcome Back</h2>
                <p class="text-sm text-slate-400 mb-6 leading-relaxed">
                    Nationwide Telecommunication Transmission Network operations, feasibility workflows, and NOC provisioning.
                </p>

                <!-- Credentials Form -->
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@nttn.test" required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-0">
                            <span>Keep me logged in</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl text-sm shadow-lg shadow-blue-600/30 transition-all hover:shadow-blue-600/50">
                        Sign In with Credentials
                    </button>
                </form>
            </div>

            <div class="pt-6 text-xs text-slate-500 font-mono">
                NTTN Enterprise Provisioning &copy; 2026
            </div>
        </div>

        <!-- Right: Enterprise Role Security & Direct Access Directory -->
        <div class="md:col-span-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300">Enterprise Access Control</h3>
                    <span class="text-xs text-emerald-400 font-mono bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Password Required</span>
                </div>
                <p class="text-xs text-slate-400 mb-5 leading-relaxed">
                    All accounts and role authorizations are provisioned by the <strong>Super Administrator</strong>. Enter your assigned email address and password to access your role workspace.
                </p>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($demoUsers as $demo)
                        @php
                            $roleColors = [
                                'super_admin' => 'from-purple-900/30 to-slate-900 border-purple-500/30 text-purple-300',
                                'md' => 'from-amber-900/30 to-slate-900 border-amber-500/30 text-amber-300',
                                'reseller' => 'from-blue-900/30 to-slate-900 border-blue-500/30 text-blue-300',
                                'transmission' => 'from-cyan-900/30 to-slate-900 border-cyan-500/30 text-cyan-300',
                                'noc' => 'from-emerald-900/30 to-slate-900 border-emerald-500/30 text-emerald-300',
                                'billing' => 'from-rose-900/30 to-slate-900 border-rose-500/30 text-rose-300',
                            ];
                            $roleColor = $roleColors[$demo->role->name] ?? 'from-slate-800 to-slate-900 border-slate-700 text-slate-300';

                            $roleDescriptions = [
                                'super_admin' => 'Full administrative command, user management & role oversight',
                                'md' => 'Commercial review, feasibility sanction & final commission',
                                'reseller' => 'Subscriber link management, survey requests & tickets',
                                'transmission' => 'Field feasibility, fiber route surveys & optical work',
                                'noc' => '8-Point SLA testing, live fiber monitoring & diagnostics',
                                'billing' => 'Revenue provisioning, monthly tariffs & carrier invoices',
                            ];
                        @endphp
                        <div onclick="document.querySelector('input[name=email]').value='{{ $demo->email }}'; document.querySelector('input[name=password]').focus();"
                             class="cursor-pointer p-3.5 rounded-2xl bg-gradient-to-br {{ $roleColor }} border hover:border-slate-500/60 transition-all duration-200 shadow-md group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-extrabold uppercase tracking-wider font-mono">{{ $demo->role->name }}</span>
                                <span class="text-[10px] text-slate-400 group-hover:text-cyan-300 transition-colors">Select &rarr;</span>
                            </div>
                            <div class="text-sm font-semibold text-white truncate">{{ $demo->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5 truncate">{{ $demo->email }}</div>
                            <div class="text-[10px] text-slate-400 mt-2 line-clamp-1 border-t border-slate-800/80 pt-1.5">{{ $roleDescriptions[$demo->role->name] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Workflow hint footer -->
            <div class="mt-6 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 font-mono flex items-center justify-between">
                <span>Security Protocol: Role-Based Access Control (RBAC)</span>
                <span class="text-cyan-400">NTTN Core v2026</span>
            </div>
        </div>

    </div>

    <script>
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
