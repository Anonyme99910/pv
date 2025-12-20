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
        .endpoint-card { transition: all 0.3s ease; }
        .endpoint-card:hover { transform: translateX(4px); }
        .endpoint-details { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .endpoint-details.open { max-height: 1000px; }
        .status-dot { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-950 text-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-slate-900/95 backdrop-blur border-b border-slate-700/50">
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
                <div class="flex items-center gap-4">
                    <button onclick="checkAllHealth()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Check API Health
                    </button>
                    <span id="overall-status" class="px-3 py-1 bg-slate-800 rounded-full text-xs font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 bg-slate-500 rounded-full"></span>
                        Checking...
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="pt-28 pb-16 bg-gradient-to-b from-slate-900 to-slate-950">
        <div class="max-w-7xl mx-auto px-6">
            <div class="max-w-3xl">
                <h1 class="text-4xl font-extrabold text-white mb-4">
                    Solar Panel Monitoring <span class="text-amber-400">REST API</span>
                </h1>
                <p class="text-lg text-slate-400 mb-6">
                    Complete API documentation with live health status. Click any endpoint to expand details.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="px-4 py-3 bg-slate-800/80 rounded-xl border border-slate-700">
                        <span class="text-slate-400 text-sm">Base URL</span>
                        <a href="{{ config('app.url') }}/api" target="_blank" class="block text-amber-400 font-mono text-sm hover:underline">
                            {{ config('app.url') }}/api
                        </a>
                    </div>
                    <div class="px-4 py-3 bg-slate-800/80 rounded-xl border border-slate-700">
                        <span class="text-slate-400 text-sm">Health Check</span>
                        <a href="{{ config('app.url') }}/api/health" target="_blank" class="block text-emerald-400 font-mono text-sm hover:underline">
                            {{ config('app.url') }}/api/health
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Health Status Cards -->
    <section class="py-8 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Service Health Status
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4" id="health-cards">
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-center">
                    <div class="text-2xl mb-1" id="health-app">⏳</div>
                    <div class="text-sm text-slate-400">Laravel App</div>
                </div>
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-center">
                    <div class="text-2xl mb-1" id="health-db">⏳</div>
                    <div class="text-sm text-slate-400">Database</div>
                </div>
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-center">
                    <div class="text-2xl mb-1" id="health-cache">⏳</div>
                    <div class="text-sm text-slate-400">Cache</div>
                </div>
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-center">
                    <div class="text-2xl mb-1" id="health-redis">⏳</div>
                    <div class="text-sm text-slate-400">Redis</div>
                </div>
                <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-center">
                    <div class="text-2xl mb-1" id="health-ai">⏳</div>
                    <div class="text-sm text-slate-400">AI Service</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Endpoints -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-6">

            <!-- Health Check Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center">💚</span>
                    Health Check
                    <span class="text-xs px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-full">Public</span>
                </h3>
                <div class="space-y-2">
                    @php
                    $healthEndpoints = [
                        ['method' => 'GET', 'path' => '/api/health', 'desc' => 'Complete system health check', 'auth' => false],
                        ['method' => 'GET', 'path' => '/api/health/ping', 'desc' => 'Quick ping for load balancers', 'auth' => false],
                        ['method' => 'GET', 'path' => '/api/health/system', 'desc' => 'System information', 'auth' => false],
                    ];
                    @endphp
                    @foreach($healthEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono flex-1">{{ $ep['path'] }}</code>
                            <a href="{{ config('app.url') }}{{ $ep['path'] }}" target="_blank" class="text-amber-400 hover:text-amber-300 text-sm" onclick="event.stopPropagation()">Try it →</a>
                            <span class="text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300 mb-3">{{ $ep['desc'] }}</p>
                            <div class="text-sm">
                                <span class="text-slate-500">Full URL:</span>
                                <a href="{{ config('app.url') }}{{ $ep['path'] }}" target="_blank" class="text-amber-400 hover:underline font-mono ml-2">{{ config('app.url') }}{{ $ep['path'] }}</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Authentication Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">🔑</span>
                    Authentication
                </h3>
                <div class="space-y-2">
                    @php
                    $authEndpoints = [
                        ['method' => 'POST', 'path' => '/api/login', 'desc' => 'User login - returns Bearer token', 'auth' => false, 'body' => '{"email": "admin@soma.com", "password": "admin123"}'],
                        ['method' => 'POST', 'path' => '/api/logout', 'desc' => 'User logout', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/user', 'desc' => 'Get current authenticated user', 'auth' => true],
                    ];
                    @endphp
                    @foreach($authEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            @if($ep['auth'])<span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>@endif
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300 mb-3">{{ $ep['desc'] }}</p>
                            @if(isset($ep['body']))
                            <div class="mb-3">
                                <span class="text-slate-500 text-sm">Request Body:</span>
                                <pre class="mt-2 p-3 bg-slate-950 rounded-lg text-sm text-emerald-400 overflow-x-auto">{{ $ep['body'] }}</pre>
                            </div>
                            @endif
                            <div class="text-sm">
                                <span class="text-slate-500">Full URL:</span>
                                <span class="text-amber-400 font-mono ml-2">{{ config('app.url') }}{{ $ep['path'] }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Panels Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center">☀️</span>
                    Solar Panels
                </h3>
                <div class="space-y-2">
                    @php
                    $panelEndpoints = [
                        ['method' => 'GET', 'path' => '/api/panels', 'desc' => 'List all panels with pagination', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/panels/grid', 'desc' => 'Panel grid view for dashboard', 'auth' => true],
                        ['method' => 'POST', 'path' => '/api/panels', 'desc' => 'Create new panel', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/panels/{id}', 'desc' => 'Get panel details', 'auth' => true],
                        ['method' => 'PUT', 'path' => '/api/panels/{id}', 'desc' => 'Update panel', 'auth' => true],
                        ['method' => 'DELETE', 'path' => '/api/panels/{id}', 'desc' => 'Delete panel', 'auth' => true],
                    ];
                    @endphp
                    @foreach($panelEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            @if($ep['auth'])<span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>@endif
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300 mb-3">{{ $ep['desc'] }}</p>
                            <div class="text-sm">
                                <span class="text-slate-500">Full URL:</span>
                                <span class="text-amber-400 font-mono ml-2">{{ config('app.url') }}{{ $ep['path'] }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Sensors Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-cyan-500/20 rounded-lg flex items-center justify-center">📡</span>
                    Sensors (ESP32)
                    <span class="text-xs px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded-full">Public</span>
                </h3>
                <div class="space-y-2">
                    @php
                    $sensorEndpoints = [
                        ['method' => 'POST', 'path' => '/api/sensors/data', 'desc' => 'Submit sensor data from ESP32', 'auth' => false, 'body' => '{"panel_code": "PV-001", "voltage": 230.5, "current": 10.2, "temperature": 45.0}'],
                        ['method' => 'POST', 'path' => '/api/sensors/batch', 'desc' => 'Batch submit sensor data', 'auth' => false],
                    ];
                    @endphp
                    @foreach($sensorEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300 mb-3">{{ $ep['desc'] }}</p>
                            @if(isset($ep['body']))
                            <div class="mb-3">
                                <span class="text-slate-500 text-sm">Request Body:</span>
                                <pre class="mt-2 p-3 bg-slate-950 rounded-lg text-sm text-emerald-400 overflow-x-auto">{{ $ep['body'] }}</pre>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Faults Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center">⚠️</span>
                    Faults
                </h3>
                <div class="space-y-2">
                    @php
                    $faultEndpoints = [
                        ['method' => 'GET', 'path' => '/api/faults', 'desc' => 'List all faults', 'auth' => true],
                        ['method' => 'POST', 'path' => '/api/faults', 'desc' => 'Create fault manually', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/faults/{id}', 'desc' => 'Get fault details', 'auth' => true],
                        ['method' => 'POST', 'path' => '/api/faults/{id}/resolve', 'desc' => 'Resolve a fault', 'auth' => true],
                    ];
                    @endphp
                    @foreach($faultEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            @if($ep['auth'])<span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>@endif
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300">{{ $ep['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Weather Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-sky-500/20 rounded-lg flex items-center justify-center">🌤️</span>
                    Weather
                </h3>
                <div class="space-y-2">
                    @php
                    $weatherEndpoints = [
                        ['method' => 'GET', 'path' => '/api/weather/current', 'desc' => 'Current weather + solar impact', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/weather/forecast', 'desc' => 'Weather forecast', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/weather/solar-forecast', 'desc' => 'Solar efficiency prediction', 'auth' => true],
                    ];
                    @endphp
                    @foreach($weatherEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            @if($ep['auth'])<span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>@endif
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300">{{ $ep['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- AI Chat Group -->
            <div class="mb-10">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 bg-violet-500/20 rounded-lg flex items-center justify-center">🤖</span>
                    AI Chat (DeepSeek)
                </h3>
                <div class="space-y-2">
                    @php
                    $chatEndpoints = [
                        ['method' => 'POST', 'path' => '/api/chat/message', 'desc' => 'Send message to AI', 'auth' => true, 'body' => '{"message": "What panels need maintenance?"}'],
                        ['method' => 'GET', 'path' => '/api/chat/history', 'desc' => 'Get chat history', 'auth' => true],
                        ['method' => 'DELETE', 'path' => '/api/chat/history', 'desc' => 'Clear chat history', 'auth' => true],
                        ['method' => 'GET', 'path' => '/api/chat/suggestions', 'desc' => 'Get smart suggestions', 'auth' => true],
                    ];
                    @endphp
                    @foreach($chatEndpoints as $ep)
                    <div class="endpoint-card bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="p-4 flex items-center gap-4 cursor-pointer hover:bg-slate-800" onclick="toggleEndpoint(this)">
                            <span class="method-{{ strtolower($ep['method']) }} px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-lg">{{ $ep['method'] }}</span>
                            <code class="text-slate-200 font-mono">{{ $ep['path'] }}</code>
                            @if($ep['auth'])<span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-medium">Auth</span>@endif
                            <span class="ml-auto text-slate-400 text-sm hidden md:block">{{ $ep['desc'] }}</span>
                            <svg class="w-5 h-5 text-slate-500 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div class="endpoint-details border-t border-slate-700 bg-slate-900/50 p-4">
                            <p class="text-slate-300 mb-3">{{ $ep['desc'] }}</p>
                            @if(isset($ep['body']))
                            <div class="mb-3">
                                <span class="text-slate-500 text-sm">Request Body:</span>
                                <pre class="mt-2 p-3 bg-slate-950 rounded-lg text-sm text-emerald-400 overflow-x-auto">{{ $ep['body'] }}</pre>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </section>

    <!-- Test Credentials -->
    <section class="py-12 bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-gradient-to-r from-amber-500/10 to-orange-500/10 rounded-2xl p-8 border border-amber-500/30">
                <h3 class="text-2xl font-bold text-white mb-6">🧪 Test Credentials</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-amber-400 font-semibold mb-2">Admin</div>
                        <div class="text-sm text-slate-300 font-mono">
                            <div>admin@soma.com</div>
                            <div>admin123</div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-blue-400 font-semibold mb-2">Technician</div>
                        <div class="text-sm text-slate-300 font-mono">
                            <div>tech@soma.com</div>
                            <div>tech123</div>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-800/50 rounded-xl">
                        <div class="text-purple-400 font-semibold mb-2">Viewer</div>
                        <div class="text-sm text-slate-300 font-mono">
                            <div>viewer@soma.com</div>
                            <div>viewer123</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 border-t border-slate-800 text-center">
        <p class="text-slate-500 text-sm">SOMA PV API Documentation v1.0.0 | © 2025</p>
    </footer>

    <script>
        const BASE_URL = '{{ config("app.url") }}';

        function toggleEndpoint(el) {
            const details = el.nextElementSibling;
            const chevron = el.querySelector('.chevron');
            details.classList.toggle('open');
            chevron.style.transform = details.classList.contains('open') ? 'rotate(180deg)' : '';
        }

        async function checkAllHealth() {
            const statusEl = document.getElementById('overall-status');
            statusEl.innerHTML = '<span class="w-2 h-2 bg-amber-500 rounded-full status-dot"></span> Checking...';

            try {
                const response = await fetch(BASE_URL + '/api/health');
                const data = await response.json();

                // Update individual health cards - use 'laravel' key for app
                updateHealthCard('health-app', data.services?.laravel?.status);
                updateHealthCard('health-db', data.services?.database?.status);
                updateHealthCard('health-cache', data.services?.cache?.status);
                updateHealthCard('health-redis', data.services?.redis?.status);
                updateHealthCard('health-ai', data.services?.ai_service?.status);

                // Update overall status
                if (data.status === 'healthy') {
                    statusEl.innerHTML = '<span class="w-2 h-2 bg-emerald-500 rounded-full"></span> All Systems Operational';
                    statusEl.className = 'px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-semibold flex items-center gap-2';
                } else if (data.status === 'degraded') {
                    statusEl.innerHTML = '<span class="w-2 h-2 bg-amber-500 rounded-full"></span> Degraded';
                    statusEl.className = 'px-3 py-1 bg-amber-500/20 text-amber-400 rounded-full text-xs font-semibold flex items-center gap-2';
                } else {
                    statusEl.innerHTML = '<span class="w-2 h-2 bg-red-500 rounded-full"></span> Unhealthy';
                    statusEl.className = 'px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold flex items-center gap-2';
                }
            } catch (error) {
                statusEl.innerHTML = '<span class="w-2 h-2 bg-red-500 rounded-full"></span> API Unreachable';
                statusEl.className = 'px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold flex items-center gap-2';
                updateHealthCard('health-app', 'unhealthy');
                updateHealthCard('health-db', 'unknown');
                updateHealthCard('health-cache', 'unknown');
                updateHealthCard('health-redis', 'unknown');
                updateHealthCard('health-ai', 'unknown');
            }
        }

        function updateHealthCard(id, status) {
            const el = document.getElementById(id);
            if (!el) return;

            if (status === 'healthy') {
                el.textContent = '✅';
                el.parentElement.classList.remove('border-red-500', 'border-amber-500');
                el.parentElement.classList.add('border-green-500');
            } else if (status === 'degraded') {
                el.textContent = '⚠️';
                el.parentElement.classList.remove('border-red-500', 'border-green-500');
                el.parentElement.classList.add('border-amber-500');
            } else if (status === 'unhealthy') {
                el.textContent = '❌';
                el.parentElement.classList.remove('border-green-500', 'border-amber-500');
                el.parentElement.classList.add('border-red-500');
            } else {
                el.textContent = '❓';
            }
        }

        // Update Laravel App status from the response
        function updateAppStatus(services) {
            if (services.laravel) {
                updateHealthCard('health-app', services.laravel.status);
            }
        }

        // Check health on page load
        document.addEventListener('DOMContentLoaded', checkAllHealth);
    </script>
</body>
</html>
