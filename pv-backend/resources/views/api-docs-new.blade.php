<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOMA PV - API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        solar: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        .method-get { background: linear-gradient(135deg, #10b981, #059669); }
        .method-post { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .method-put { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .method-patch { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .method-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .endpoint-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .endpoint-card:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); }
        .glow-amber { box-shadow: 0 0 60px rgba(245, 158, 11, 0.2); }
        .nav-link { transition: all 0.2s ease; }
        .nav-link:hover { color: #fbbf24; }
        .nav-link.active { color: #fbbf24; border-bottom: 2px solid #fbbf24; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        .hero-gradient { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); }
        .card-gradient { background: linear-gradient(180deg, rgba(30,41,59,0.9) 0%, rgba(15,23,42,0.95) 100%); }
        pre { background: #0f172a !important; border-radius: 8px; }
    </style>
</head>
<body class="bg-slate-950 text-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-slate-700/50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">SOMA PV</h1>
                        <p class="text-xs text-slate-400">API Documentation</p>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <a href="#overview" class="nav-link text-sm font-medium text-slate-300">Overview</a>
                    <a href="#auth" class="nav-link text-sm font-medium text-slate-300">Authentication</a>
                    <a href="#endpoints" class="nav-link text-sm font-medium text-slate-300">Endpoints</a>
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-semibold">v1.0.0</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient pt-32 pb-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-20 left-20 w-72 h-72 bg-amber-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
        </div>
        <div class="max-w-7xl mx-auto px-6 relative">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500/10 border border-amber-500/30 rounded-full mb-6">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-amber-400 text-sm font-medium">API Status: Operational</span>
                </div>
                <h1 class="text-5xl font-extrabold text-white mb-6 leading-tight">
                    Solar Panel Monitoring<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">REST API</span>
                </h1>
                <p class="text-xl text-slate-400 mb-8 leading-relaxed">
                    Complete API documentation for the SOMA PV monitoring system.
                    Integrate with our powerful endpoints for real-time solar panel data,
                    fault detection, and AI-powered analytics.
                </p>
                <div class="flex items-center gap-4">
                    <div class="px-4 py-2 bg-slate-800 rounded-lg border border-slate-700">
                        <span class="text-slate-400 text-sm">Base URL:</span>
                        <code class="text-amber-400 ml-2 font-mono">{{ config('app.url') }}/api</code>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="py-12 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center p-6 rounded-2xl card-gradient border border-slate-700/50">
                    <div class="text-4xl font-bold text-amber-400 mb-2">77</div>
                    <div class="text-slate-400 text-sm">API Endpoints</div>
                </div>
                <div class="text-center p-6 rounded-2xl card-gradient border border-slate-700/50">
                    <div class="text-4xl font-bold text-emerald-400 mb-2">12</div>
                    <div class="text-slate-400 text-sm">Resource Groups</div>
                </div>
                <div class="text-center p-6 rounded-2xl card-gradient border border-slate-700/50">
                    <div class="text-4xl font-bold text-blue-400 mb-2">REST</div>
                    <div class="text-slate-400 text-sm">API Standard</div>
                </div>
                <div class="text-center p-6 rounded-2xl card-gradient border border-slate-700/50">
                    <div class="text-4xl font-bold text-purple-400 mb-2">JSON</div>
                    <div class="text-slate-400 text-sm">Response Format</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Authentication Section -->
    <section id="auth" class="py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">Authentication</h2>
                    <p class="text-slate-400">Secure your API requests with Bearer tokens</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="card-gradient rounded-2xl p-8 border border-slate-700/50">
                    <h3 class="text-xl font-semibold text-white mb-4">🔐 How it works</h3>
                    <ol class="space-y-4 text-slate-300">
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-amber-500/20 text-amber-400 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                            <span>Call <code class="text-amber-400 bg-slate-800 px-2 py-0.5 rounded">POST /api/login</code> with email & password</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-amber-500/20 text-amber-400 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                            <span>Receive a Bearer token in the response</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="w-6 h-6 bg-amber-500/20 text-amber-400 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                            <span>Include token in all subsequent requests</span>
                        </li>
                    </ol>
                </div>

                <div class="card-gradient rounded-2xl p-8 border border-slate-700/50">
                    <h3 class="text-xl font-semibold text-white mb-4">📝 Example Request</h3>
                    <pre class="text-sm overflow-x-auto p-4 rounded-lg bg-slate-900"><code class="text-slate-300">// Login Request
POST /api/login
Content-Type: application/json

{
  "email": "admin@soma.com",
  "password": "admin123"
}

// Response
{
  "user": { "id": 1, "name": "Admin", "role": "admin" },
  "token": "1|abc123xyz..."
}</code></pre>
                </div>
            </div>

            <div class="mt-8 p-6 bg-amber-500/10 border border-amber-500/30 rounded-2xl">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-amber-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-amber-400 font-semibold mb-1">Authorization Header</h4>
                        <p class="text-slate-300 text-sm">Include this header in all authenticated requests:</p>
                        <code class="block mt-2 text-amber-400 bg-slate-900 px-4 py-2 rounded-lg font-mono text-sm">Authorization: Bearer {your_token}</code>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Endpoints Section -->
    <section id="endpoints" class="py-16 bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center gap-3 mb-12">
                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">API Endpoints</h2>
                    <p class="text-slate-400">Complete reference for all available endpoints</p>
                </div>
            </div>

            <!-- Health Check -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center">💚</span>
                    Health Check
                    <span class="text-xs px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-full">Public</span>
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/health</code>
                            <span class="ml-auto text-slate-400 text-sm">Complete system health check</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/health/ping</code>
                            <span class="ml-auto text-slate-400 text-sm">Quick ping for load balancers</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/health/system</code>
                            <span class="ml-auto text-slate-400 text-sm">System information</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">🔑</span>
                    Authentication
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/login</code>
                            <span class="ml-auto text-slate-400 text-sm">User login</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/logout</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">User logout</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/user</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get current user</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">📊</span>
                    Dashboard
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/dashboard</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get dashboard KPIs, charts, alerts</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panels -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center">☀️</span>
                    Solar Panels
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/panels</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">List all panels</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/panels/grid</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Panel grid view</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/panels</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Create new panel</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get panel details</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-put px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">PUT</span>
                            <code class="text-slate-200 font-mono">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Update panel</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-delete px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">DELETE</span>
                            <code class="text-slate-200 font-mono">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Delete panel</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensors -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center">📡</span>
                    Sensors (ESP32)
                    <span class="text-xs px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-full">Public</span>
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/sensors/data</code>
                            <span class="ml-auto text-slate-400 text-sm">Submit sensor data from ESP32</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/sensors/batch</code>
                            <span class="ml-auto text-slate-400 text-sm">Batch submit sensor data</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Faults -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center">⚠️</span>
                    Faults
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/faults</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">List all faults</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/faults/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get fault details</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/faults/{id}/resolve</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Resolve a fault</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-indigo-500/20 rounded-lg flex items-center justify-center">🔧</span>
                    Maintenance
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/maintenance</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">List maintenance tasks</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/maintenance/calendar</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Calendar view</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/maintenance/{id}/complete</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Complete task</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weather -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center">🌤️</span>
                    Weather
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/weather/current</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Current weather + solar impact</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/weather/forecast</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Weather forecast</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/weather/solar-forecast</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Solar efficiency prediction</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Chat -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-violet-500/20 rounded-lg flex items-center justify-center">🤖</span>
                    AI Chat Assistant
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/chat/message</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Send message to AI</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/chat/history</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get chat history</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/chat/suggestions</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>
                            <span class="ml-auto text-slate-400 text-sm">Get smart suggestions</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-slate-500/20 rounded-lg flex items-center justify-center">⚙️</span>
                    Settings
                    <span class="text-xs px-2 py-1 bg-red-500/20 text-red-400 rounded-full">Admin Only</span>
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/settings/smtp</code>
                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs font-medium">Admin</span>
                            <span class="ml-auto text-slate-400 text-sm">SMTP configuration</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/settings/ai</code>
                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs font-medium">Admin</span>
                            <span class="ml-auto text-slate-400 text-sm">AI configuration</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-get px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">GET</span>
                            <code class="text-slate-200 font-mono">/api/settings/weather</code>
                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs font-medium">Admin</span>
                            <span class="ml-auto text-slate-400 text-sm">Weather API configuration</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhooks -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 bg-orange-500/20 rounded-lg flex items-center justify-center">🔗</span>
                    AI Webhooks
                    <span class="text-xs px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-full">From Python AI</span>
                </h3>
                <div class="space-y-3">
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/webhooks/ai/fault-prediction</code>
                            <span class="ml-auto text-slate-400 text-sm">Receive AI fault prediction</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/webhooks/ai/rul-prediction</code>
                            <span class="ml-auto text-slate-400 text-sm">Receive RUL prediction</span>
                        </div>
                    </div>
                    <div class="endpoint-card card-gradient rounded-xl p-5 border border-slate-700/50">
                        <div class="flex items-center gap-4">
                            <span class="method-post px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">POST</span>
                            <code class="text-slate-200 font-mono">/api/webhooks/ai/image-classification</code>
                            <span class="ml-auto text-slate-400 text-sm">Receive image classification</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Test Credentials -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="card-gradient rounded-2xl p-8 border border-slate-700/50 glow-amber">
                <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center">🧪</span>
                    Test Credentials
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-amber-400 font-semibold mb-2">Admin User</div>
                        <div class="text-sm text-slate-300">
                            <div>Email: <code class="text-emerald-400">admin@soma.com</code></div>
                            <div>Password: <code class="text-emerald-400">admin123</code></div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-blue-400 font-semibold mb-2">Technician</div>
                        <div class="text-sm text-slate-300">
                            <div>Email: <code class="text-emerald-400">tech@soma.com</code></div>
                            <div>Password: <code class="text-emerald-400">tech123</code></div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-purple-400 font-semibold mb-2">Viewer</div>
                        <div class="text-sm text-slate-300">
                            <div>Email: <code class="text-emerald-400">viewer@soma.com</code></div>
                            <div>Password: <code class="text-emerald-400">viewer123</code></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-amber-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold text-white">SOMA PV</span>
            </div>
            <p class="text-slate-400 text-sm">Solar Panel Monitoring System - API Documentation v1.0.0</p>
            <p class="text-slate-500 text-xs mt-2">© 2025 SOMA. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Smooth scroll for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Active nav link on scroll
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');

            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
