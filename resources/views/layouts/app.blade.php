<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'NTTN Service Portal' }} — Telecommunications Network Management</title>
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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN with typography and forms -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            emerald: '#10b981',
                            amber: '#f59e0b',
                            rose: '#f43f5e',
                            violet: '#8b5cf6',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Smooth transitions for theme switching */
        body, aside, header, main, div, nav, table, th, td, input, select, textarea, button {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glow-brand {
            box-shadow: 0 0 25px -5px rgba(59, 130, 246, 0.3);
        }

        /* Custom scrollbar in dark mode */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0b0f19; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }

        /* ========================================================
           LIGHT THEME DEFINITIONS (Applied when html is not .dark)
           ======================================================== */
        html:not(.dark) {
            color-scheme: light;
        }

        html:not(.dark) body {
            background-color: #f8fafc !important;
            background-image: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%) !important;
            color: #0f172a !important;
        }

        /* Sidebar in Light Mode */
        html:not(.dark) aside {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 2px 0 8px -2px rgba(0, 0, 0, 0.04) !important;
        }
        html:not(.dark) aside > div:first-child {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) aside a .bg-clip-text {
            background: linear-gradient(to right, #0f172a, #334155) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }
        html:not(.dark) aside .bg-slate-900\/60,
        html:not(.dark) aside .bg-slate-950\/60,
        html:not(.dark) aside .bg-slate-950\/40 {
            background-color: #f8fafc !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) aside nav a:not([class*="bg-blue-600"]) {
            color: #475569 !important;
        }
        html:not(.dark) aside nav a:not([class*="bg-blue-600"]):hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }
        html:not(.dark) aside nav div[class*="text-slate-500"] {
            color: #94a3b8 !important;
        }
        html:not(.dark) aside .text-slate-200 {
            color: #0f172a !important;
        }
        html:not(.dark) aside .text-slate-400 {
            color: #64748b !important;
        }

        /* Topbar Header in Light Mode */
        html:not(.dark) header {
            background-color: rgba(255, 255, 255, 0.9) !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03) !important;
        }
        html:not(.dark) header h1 {
            color: #0f172a !important;
        }

        /* Quick Switcher */
        html:not(.dark) header [class*="bg-slate-900\/90"] {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
        }
        html:not(.dark) header [class*="bg-slate-900\/90"] button:not([class*="bg-"]) {
            color: #475569 !important;
        }
        html:not(.dark) header [class*="bg-slate-900\/90"] button:not([class*="bg-"]):hover {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            background-color: rgba(30, 41, 59, 0.8);
            border-color: rgba(51, 65, 85, 0.8);
            color: #cbd5e1;
        }
        .theme-toggle-btn:hover {
            background-color: rgba(51, 65, 85, 0.95);
            color: #ffffff;
        }
        html:not(.dark) .theme-toggle-btn {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #334155 !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
        }
        html:not(.dark) .theme-toggle-btn:hover {
            background-color: #f8fafc !important;
            color: #0f172a !important;
        }

        /* Panels & Cards in Light Mode */
        html:not(.dark) .glass-panel {
            background: rgba(255, 255, 255, 0.96) !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
        }

        html:not(.dark) [class*="bg-slate-900"],
        html:not(.dark) [class*="bg-slate-950"]:not(body):not(aside) {
            background-color: #ffffff !important;
        }

        html:not(.dark) [class*="border-slate-800"],
        html:not(.dark) [class*="border-slate-700"]:not(button):not(input):not(select):not(textarea) {
            border-color: #e2e8f0 !important;
        }

        html:not(.dark) .rounded-2xl.border,
        html:not(.dark) .rounded-3xl.border,
        html:not(.dark) .rounded-xl.border {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.06), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
        }

        /* Hero / Welcome Banner */
        html:not(.dark) [class*="from-blue-950"] {
            background-image: linear-gradient(to right, #eff6ff 0%, #e0e7ff 50%, #ffffff 100%) !important;
            border-color: #bfdbfe !important;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.08) !important;
        }
        html:not(.dark) [class*="from-blue-950"] h2 {
            color: #1e3a8a !important;
        }
        html:not(.dark) [class*="from-blue-950"] p {
            color: #334155 !important;
        }
        html:not(.dark) [class*="from-blue-950"] [class*="bg-blue-500\/10"] {
            background-color: rgba(59, 130, 246, 0.15) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
            color: #1d4ed8 !important;
        }

        /* Text Overrides in Light Mode */
        html:not(.dark) .text-white:not(button):not(a[class*="bg-"]):not(span[class*="bg-"]) {
            color: #0f172a !important;
        }
        html:not(.dark) .text-slate-100 { color: #1e293b !important; }
        html:not(.dark) .text-slate-200 { color: #334155 !important; }
        html:not(.dark) .text-slate-300 { color: #475569 !important; }
        html:not(.dark) .text-slate-400 { color: #64748b !important; }
        html:not(.dark) .text-slate-500 { color: #64748b !important; }

        /* Preserve white text inside solid colored buttons & badges */
        html:not(.dark) button[class*="bg-blue-"],
        html:not(.dark) a[class*="bg-blue-"],
        html:not(.dark) button[class*="bg-amber-"],
        html:not(.dark) a[class*="bg-amber-"],
        html:not(.dark) button[class*="bg-emerald-"],
        html:not(.dark) a[class*="bg-emerald-"],
        html:not(.dark) button[class*="bg-cyan-"],
        html:not(.dark) a[class*="bg-cyan-"],
        html:not(.dark) button[class*="bg-purple-"],
        html:not(.dark) a[class*="bg-purple-"],
        html:not(.dark) button[class*="bg-rose-"],
        html:not(.dark) a[class*="bg-rose-"],
        html:not(.dark) button[class*="bg-gradient-"],
        html:not(.dark) a[class*="bg-gradient-"],
        html:not(.dark) aside nav a[class*="bg-blue-600"] {
            color: #ffffff !important;
        }
        html:not(.dark) button[class*="bg-blue-"] *,
        html:not(.dark) a[class*="bg-blue-"] *,
        html:not(.dark) button[class*="bg-amber-"] *,
        html:not(.dark) a[class*="bg-amber-"] *,
        html:not(.dark) button[class*="bg-emerald-"] *,
        html:not(.dark) a[class*="bg-emerald-"] *,
        html:not(.dark) button[class*="bg-cyan-"] *,
        html:not(.dark) a[class*="bg-cyan-"] *,
        html:not(.dark) button[class*="bg-purple-"] *,
        html:not(.dark) a[class*="bg-purple-"] *,
        html:not(.dark) button[class*="bg-rose-"] *,
        html:not(.dark) a[class*="bg-rose-"] *,
        html:not(.dark) button[class*="bg-gradient-"] *,
        html:not(.dark) a[class*="bg-gradient-"] *,
        html:not(.dark) aside nav a[class*="bg-blue-600"] * {
            color: #ffffff !important;
        }

        /* Light Mode Secondary action buttons */
        html:not(.dark) a[class*="bg-slate-800"],
        html:not(.dark) button[class*="bg-slate-800"] {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }
        html:not(.dark) a[class*="bg-slate-800"]:hover,
        html:not(.dark) button[class*="bg-slate-800"]:hover {
            background-color: #e2e8f0 !important;
        }

        /* Status Colors - High Contrast Enhancement for Light Mode */
        html:not(.dark) .text-emerald-400 { color: #059669 !important; }
        html:not(.dark) .text-blue-400 { color: #2563eb !important; }
        html:not(.dark) .text-cyan-400 { color: #0891b2 !important; }
        html:not(.dark) .text-amber-400 { color: #d97706 !important; }
        html:not(.dark) .text-purple-400 { color: #7c3aed !important; }
        html:not(.dark) .text-rose-400 { color: #e11d48 !important; }

        html:not(.dark) .border-emerald-500\/20 { border-color: rgba(16, 185, 129, 0.35) !important; }
        html:not(.dark) .border-blue-500\/20 { border-color: rgba(59, 130, 246, 0.35) !important; }
        html:not(.dark) .border-cyan-500\/20 { border-color: rgba(6, 182, 212, 0.35) !important; }
        html:not(.dark) .border-amber-500\/20 { border-color: rgba(245, 158, 11, 0.35) !important; }
        html:not(.dark) .border-purple-500\/20 { border-color: rgba(139, 92, 246, 0.35) !important; }
        html:not(.dark) .border-rose-500\/20 { border-color: rgba(244, 63, 94, 0.35) !important; }

        /* Tables in Light Mode */
        html:not(.dark) table {
            background-color: #ffffff !important;
        }
        html:not(.dark) table thead,
        html:not(.dark) table thead tr,
        html:not(.dark) table thead th {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            border-color: #e2e8f0 !important;
        }
        html:not(.dark) table tbody tr {
            border-color: #f1f5f9 !important;
            color: #334155 !important;
        }
        html:not(.dark) table tbody tr:hover {
            background-color: #f8fafc !important;
        }
        html:not(.dark) table td {
            border-color: #e2e8f0 !important;
        }

        /* Form Inputs & Selects in Light Mode */
        html:not(.dark) input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="hidden"]),
        html:not(.dark) select,
        html:not(.dark) textarea {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        html:not(.dark) select option {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        html:not(.dark) input::placeholder,
        html:not(.dark) textarea::placeholder {
            color: #94a3b8 !important;
        }
        html:not(.dark) input:focus,
        html:not(.dark) select:focus,
        html:not(.dark) textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
        }
        html:not(.dark) label {
            color: #475569 !important;
        }

        /* Light Theme Scrollbars */
        html:not(.dark) ::-webkit-scrollbar { width: 6px; height: 6px; }
        html:not(.dark) ::-webkit-scrollbar-track { background: #f1f5f9; }
        html:not(.dark) ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        html:not(.dark) ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-full flex flex-col bg-gradient-to-br from-slate-950 via-slate-900 to-[#0a0e1a] antialiased text-slate-100">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-72 bg-slate-900/90 border-r border-slate-800 flex flex-col flex-shrink-0 z-20">
            <!-- Brand header -->
            <div class="h-18 px-6 flex items-center justify-between border-b border-slate-800 bg-slate-950/40 py-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-400 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-base font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">NTTN PORTAL</div>
                        <div class="text-[11px] font-medium text-cyan-400 uppercase tracking-widest">Enterprise Core</div>
                    </div>
                </a>
            </div>

            <!-- Role Badge Banner -->
            <div class="px-5 py-3 border-b border-slate-800/80 bg-slate-900/60">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Current Role</span>
                    <span class="px-2.5 py-1 rounded-full font-bold uppercase tracking-wider text-[10px]
                        @if(auth()->user()->isSuperAdmin()) bg-purple-500/10 text-purple-400 border border-purple-500/20
                        @elseif(auth()->user()->isMd()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                        @elseif(auth()->user()->isReseller()) bg-blue-500/10 text-blue-400 border border-blue-500/20
                        @elseif(auth()->user()->isTransmission()) bg-cyan-500/10 text-cyan-400 border border-cyan-500/20
                        @elseif(auth()->user()->isNoc()) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                        @else bg-rose-500/10 text-rose-400 border border-rose-500/20 @endif">
                        {{ auth()->user()->role->name }}
                    </span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
                <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider px-3 mb-2">Main Navigation</div>

                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('surveys.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('surveys.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span>Surveys & Orders</span>
                    </div>
                    @if(auth()->user()->isReseller())
                        <span class="text-xs bg-blue-500/20 text-blue-300 px-1.5 py-0.5 rounded font-mono">+ New</span>
                    @endif
                </a>

                @if(auth()->user()->isMd() || auth()->user()->isSuperAdmin())
                <a href="{{ route('approvals.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('approvals.*') ? 'bg-amber-600/20 text-amber-400 border border-amber-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>MD Approvals</span>
                    </div>
                    <span class="text-xs bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-mono font-bold">Review</span>
                </a>
                @endif

                @if(auth()->user()->isTransmission() || auth()->user()->isSuperAdmin())
                <a href="{{ route('work-orders.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('work-orders.*') ? 'bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Field Work Orders</span>
                </a>
                @endif

                @if(auth()->user()->isNoc() || auth()->user()->isSuperAdmin())
                <a href="{{ route('noc.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('noc.*') ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>NOC SLA Testing</span>
                </a>
                @endif

                @if(auth()->user()->isBilling() || auth()->user()->isMd() || auth()->user()->isSuperAdmin() || auth()->user()->isReseller())
                <a href="{{ route('billing.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('billing.*') ? 'bg-violet-600/20 text-violet-400 border border-violet-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Billing & Invoices</span>
                </a>
                @endif

                <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider px-3 pt-4 mb-2">Live Operations</div>

                <a href="{{ route('links.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('links.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span>Active Circuits</span>
                </a>

                <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('tickets.*') ? 'bg-rose-600/20 text-rose-400 border border-rose-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span>Trouble Tickets</span>
                </a>

                <a href="{{ route('service-requests.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('service-requests.*') ? 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Bandwidth Requests</span>
                </a>

                <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider px-3 pt-4 mb-2">System</div>

                @if(auth()->user()->isSuperAdmin() || session()->has('impersonator_id'))
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('users.*') ? 'bg-purple-600/20 text-purple-400 border border-purple-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>User & Role Access</span>
                </a>

                <a href="{{ route('backups.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('backups.*') ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    <span>Disaster Recovery & Backup</span>
                </a>

                <a href="{{ route('admin.mail-settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.mail-settings.*') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Mail & Carrier Settings</span>
                </a>
                @endif

                <a href="{{ route('activity-logs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('activity-logs.*') ? 'bg-slate-700/50 text-slate-200 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Activity Audit Trail</span>
                </a>
            </nav>

            <!-- User profile & logout footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-slate-700 to-slate-600 border border-slate-700 flex items-center justify-center font-bold text-sm text-white">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-semibold text-slate-200 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono truncate">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            @if(session()->has('impersonator_id'))
            <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-purple-950 border-b border-purple-500/30 px-8 py-2.5 flex items-center justify-between text-xs text-purple-200 z-30 shadow-md">
                <div class="flex items-center gap-3">
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-purple-500"></span>
                    </span>
                    <span>
                        <strong class="text-white">Super Admin Access:</strong> Logged in as <span class="font-bold text-cyan-300">{{ auth()->user()->name }}</span> (<span class="font-mono uppercase text-purple-300">{{ auth()->user()->role->name }}</span>)
                    </span>
                </div>
                <form method="POST" action="{{ route('users.stop-impersonate') }}">
                    @csrf
                    <button type="submit" class="px-3.5 py-1 bg-purple-600 hover:bg-purple-500 text-white font-semibold rounded-lg text-xs shadow transition-all flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Return to Super Admin</span>
                    </button>
                </form>
            </div>
            @endif

            <!-- Topbar Header -->
            <header class="h-16 border-b border-slate-800/80 bg-slate-900/40 backdrop-blur-md px-8 flex items-center justify-between z-10">
                <!-- Breadcrumbs / Page Title -->
                <div class="flex items-center gap-4">
                    <h1 class="text-lg font-bold text-white tracking-tight">{{ $header ?? 'NTTN Operations' }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Light / Dark Theme Switcher Button -->
                    <button id="themeToggleBtn" type="button" onclick="toggleTheme()" 
                        class="theme-toggle-btn flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold cursor-pointer shadow-sm transition-all duration-200"
                        title="Switch between Light and Dark mode" aria-label="Toggle theme">
                        <!-- Sun Icon (shown in dark mode, click switches to light) -->
                        <span class="theme-icon-sun hidden items-center gap-1.5 text-amber-400">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="text-slate-300 font-mono text-[11px] hidden sm:inline">Light</span>
                        </span>
                        <!-- Moon Icon (shown in light mode, click switches to dark) -->
                        <span class="theme-icon-moon flex items-center gap-1.5 text-indigo-600">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <span class="text-slate-700 font-mono text-[11px] hidden sm:inline">Dark</span>
                        </span>
                    </button>

                    @if(auth()->user()->isSuperAdmin() || session()->has('impersonator_id'))
                    <!-- Quick Role Switcher Bar for Super Admin -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-purple-300 font-mono hidden md:inline">Admin Switcher:</span>
                        <div class="flex items-center gap-1.5 bg-slate-900/90 border border-purple-500/30 p-1 rounded-xl shadow-inner">
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="admin@nttn.test">
                                <button type="submit" title="Switch to Super Admin" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isSuperAdmin() && !session()->has('impersonator_id') ? 'bg-purple-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">Admin</button>
                            </form>
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="md@nttn.test">
                                <button type="submit" title="Switch to MD" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isMd() ? 'bg-amber-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">MD</button>
                            </form>
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="reseller@isp.test">
                                <button type="submit" title="Switch to Reseller (ISP)" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isReseller() ? 'bg-blue-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">Reseller</button>
                            </form>
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="tx@nttn.test">
                                <button type="submit" title="Switch to Transmission" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isTransmission() ? 'bg-cyan-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">Tx</button>
                            </form>
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="noc@nttn.test">
                                <button type="submit" title="Switch to NOC" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isNoc() ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">NOC</button>
                            </form>
                            <form method="POST" action="{{ route('login.quick') }}">
                                @csrf
                                <input type="hidden" name="email" value="billing@nttn.test">
                                <button type="submit" title="Switch to Billing" class="px-2.5 py-1 text-xs rounded-lg font-medium transition-colors {{ auth()->user()->isBilling() ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">Billing</button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </header>

            <!-- Notification Flashes -->
            <div class="px-8 pt-4">
                @if(session('success'))
                    <div class="p-3.5 mb-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 text-lg">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-3.5 mb-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-rose-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-200 text-lg">&times;</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="p-3.5 mb-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-sm flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>{{ session('warning') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-amber-200 text-lg">&times;</button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-3.5 mb-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                        <div class="font-semibold mb-1">Please fix the following:</div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto px-8 pb-10">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')

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
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: nextTheme } }));
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
