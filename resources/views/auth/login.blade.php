<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — NTTN Service Management Portal</title>
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
    </style>
</head>
<body class="min-h-full flex items-center justify-center bg-gradient-to-br from-slate-950 via-[#0b1329] to-slate-950 p-6">
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
                        <input type="email" name="email" value="{{ old('email', 'admin@nttn.test') }}" required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" value="password" required
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-0">
                            <span>Keep me logged in</span>
                        </label>
                        <span class="font-mono text-slate-500">Default: password</span>
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

        <!-- Right: 1-Click Role-Switch Demo Hub -->
        <div class="md:col-span-3 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-300">Quick Demo Role Access</h3>
                    <span class="text-xs text-cyan-400 font-mono bg-cyan-500/10 px-2 py-0.5 rounded-full border border-cyan-500/20">Instant 1-Click Login</span>
                </div>
                <p class="text-xs text-slate-400 mb-5 leading-relaxed">
                    Select any role to instantly log in and test that exact actor's viewpoint and workflows:
                </p>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($demoUsers as $demo)
                        @php
                            $roleColors = [
                                'super_admin' => 'from-purple-900/40 to-slate-900 border-purple-500/30 hover:border-purple-400 text-purple-300',
                                'md' => 'from-amber-900/40 to-slate-900 border-amber-500/30 hover:border-amber-400 text-amber-300',
                                'reseller' => 'from-blue-900/40 to-slate-900 border-blue-500/30 hover:border-blue-400 text-blue-300',
                                'transmission' => 'from-cyan-900/40 to-slate-900 border-cyan-500/30 hover:border-cyan-400 text-cyan-300',
                                'noc' => 'from-emerald-900/40 to-slate-900 border-emerald-500/30 hover:border-emerald-400 text-emerald-300',
                                'billing' => 'from-rose-900/40 to-slate-900 border-rose-500/30 hover:border-rose-400 text-rose-300',
                            ];
                            $roleColor = $roleColors[$demo->role->name] ?? 'from-slate-800 to-slate-900 border-slate-700 text-slate-300';
                            
                            $roleRoles = [
                                'super_admin' => 'Full administrative command & oversight',
                                'md' => 'Commercial approvals & final commissioning',
                                'reseller' => 'Order requests & customer links',
                                'transmission' => 'Feasibility survey & optical field work',
                                'noc' => '8-Point SLA verification & active monitoring',
                                'billing' => 'Invoicing, rates & carrier charges',
                            ];
                        @endphp
                        <form method="POST" action="{{ route('login.quick') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $demo->email }}">
                            <button type="submit" class="w-full text-left p-3.5 rounded-2xl bg-gradient-to-br {{ $roleColor }} border transition-all duration-200 hover:scale-[1.02] shadow-md group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-extrabold uppercase tracking-wider font-mono">{{ $demo->role->name }}</span>
                                    <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                                <div class="text-sm font-semibold text-white group-hover:text-blue-200 truncate">{{ $demo->name }}</div>
                                <div class="text-[11px] text-slate-400 mt-1 line-clamp-1">{{ $roleRoles[$demo->role->name] ?? '' }}</div>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            <!-- Workflow hint footer -->
            <div class="mt-6 p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 font-mono flex items-center justify-between">
                <span>Core Path: Reseller &rarr; Tx &rarr; MD &rarr; NOC &rarr; Billing &rarr; Active</span>
                <span class="text-blue-400">100% Audited</span>
            </div>
        </div>

    </div>
</body>
</html>
