<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOMA PV API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                }
            }
        }
    </script>
    <style>
        .method-get { background: linear-gradient(135deg, #10b981, #059669); }
        .method-post { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .method-put { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .method-patch { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .method-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .endpoint-card { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .endpoint-card:hover { transform: translateX(4px); border-left-color: #f59e0b; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); }
        .glow { box-shadow: 0 0 40px rgba(245, 158, 11, 0.15); }
        .sidebar-link { transition: all 0.2s ease; }
        .sidebar-link:hover { background: rgba(245, 158, 11, 0.1); }
        .sidebar-link.active { background: rgba(245, 158, 11, 0.2); border-left: 3px solid #f59e0b; }
        pre code { font-family: 'JetBrains Mono', monospace !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">SOMA PV API</h1>
                    <p class="text-gray-400">Solar Panel Monitoring System - API Documentation</p>
                </div>
            </div>
            <div class="flex gap-4 text-sm">
                <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full">Version 1.0.0</span>
                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full">Base URL: {{ config('app.url') }}/api</span>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="#health" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                <div class="text-green-400 font-semibold">Health Check</div>
                <div class="text-sm text-gray-400">System status</div>
            </a>
            <a href="#authentication" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                <div class="text-blue-400 font-semibold">Authentication</div>
                <div class="text-sm text-gray-400">Login & tokens</div>
            </a>
            <a href="#panels" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                <div class="text-amber-400 font-semibold">Panels</div>
                <div class="text-sm text-gray-400">Panel management</div>
            </a>
            <a href="#faults" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition">
                <div class="text-red-400 font-semibold">Faults</div>
                <div class="text-sm text-gray-400">Fault detection</div>
            </a>
        </div>

        <!-- Authentication Info -->
        <div class="bg-gray-800 rounded-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">🔐 Authentication</h2>
            <p class="text-gray-400 mb-4">This API uses <strong class="text-white">Laravel Sanctum</strong> for authentication. Include the token in the Authorization header:</p>
            <pre class="bg-gray-900 p-4 rounded-lg overflow-x-auto"><code class="language-bash">Authorization: Bearer {your_token}</code></pre>
            <div class="mt-4 p-4 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                <p class="text-amber-400"><strong>Note:</strong> Get your token by calling POST /api/login with email and password.</p>
            </div>
        </div>

        <!-- Endpoints -->
        <div class="space-y-8">
            <!-- Health -->
            <section id="health">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-green-500/20 rounded flex items-center justify-center">💚</span>
                    Health Check
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-700">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/health</code>
                            <span class="ml-auto text-sm text-gray-400">Complete system health check</span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-400 mb-2">Response:</p>
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "status": "healthy",
  "timestamp": "2025-12-17T12:00:00Z",
  "services": {
    "laravel": { "status": "healthy", "version": "11.x" },
    "database": { "status": "healthy", "latency_ms": 5 },
    "cache": { "status": "healthy" },
    "redis": { "status": "healthy" },
    "ai_service": { "status": "healthy", "url": "http://localhost:8001" }
  }
}</code></pre>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/health/ping</code>
                            <span class="ml-auto text-sm text-gray-400">Quick ping (for load balancers)</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/health/system</code>
                            <span class="ml-auto text-sm text-gray-400">System information</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Authentication -->
            <section id="authentication">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-blue-500/20 rounded flex items-center justify-center">🔑</span>
                    Authentication
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-700">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/login</code>
                            <span class="ml-auto text-sm text-gray-400">User login</span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-400 mb-2">Request Body:</p>
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "email": "admin@soma.com",
  "password": "password"
}</code></pre>
                            <p class="text-sm text-gray-400 mt-4 mb-2">Response:</p>
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "user": { "id": 1, "name": "Admin", "email": "admin@soma.com", "role": "admin" },
  "token": "1|abc123..."
}</code></pre>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/logout</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth Required</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/user</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth Required</span>
                            <span class="ml-auto text-sm text-gray-400">Get current user</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Panels -->
            <section id="panels">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-amber-500/20 rounded flex items-center justify-center">☀️</span>
                    Panels
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/panels</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">List all panels</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/panels/grid</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Panel grid view</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/panels</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Create panel</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Get panel details</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-put px-2 py-1 rounded text-xs font-bold text-white">PUT</span>
                            <code class="text-gray-300">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Update panel</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-delete px-2 py-1 rounded text-xs font-bold text-white">DELETE</span>
                            <code class="text-gray-300">/api/panels/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Delete panel</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sensors -->
            <section id="sensors">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-cyan-500/20 rounded flex items-center justify-center">📡</span>
                    Sensors (ESP32 Integration)
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-700">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/sensors/data</code>
                            <span class="px-2 py-0.5 bg-green-500/20 text-green-400 rounded text-xs">Public</span>
                            <span class="ml-auto text-sm text-gray-400">Submit sensor data</span>
                        </div>
                        <div class="p-4">
                            <p class="text-sm text-gray-400 mb-2">Request Body (from ESP32):</p>
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "panel_code": "PV-001",
  "irradiance": 850.5,
  "temperature": 32.5,
  "voltage": 235.0,
  "current": 8.5,
  "power_output": 125.5,
  "humidity": 65.0
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Faults -->
            <section id="faults">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-red-500/20 rounded flex items-center justify-center">⚠️</span>
                    Faults
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/faults</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">List faults</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/faults/{id}</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Fault details</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/faults/{id}/resolve</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Resolve fault</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Weather -->
            <section id="weather">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-blue-500/20 rounded flex items-center justify-center">🌤️</span>
                    Weather
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/weather/current?location=auto:ip</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Current weather + solar impact</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/weather/forecast?location=London&days=3</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Weather forecast</span>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/weather/search?q=Paris</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Search locations</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Chat -->
            <section id="chat">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-purple-500/20 rounded flex items-center justify-center">🤖</span>
                    AI Chat
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-700">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/chat/message</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Send message to AI</span>
                        </div>
                        <div class="p-4">
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "message": "What faults are currently active?",
  "context": "fault"
}</code></pre>
                        </div>
                    </div>
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4">
                            <span class="method-get px-2 py-1 rounded text-xs font-bold text-white">GET</span>
                            <code class="text-gray-300">/api/chat/history</code>
                            <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs">Auth</span>
                            <span class="ml-auto text-sm text-gray-400">Chat history</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Webhooks -->
            <section id="webhooks">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 bg-orange-500/20 rounded flex items-center justify-center">🔗</span>
                    AI Webhooks (from Python AI Service)
                </h2>
                <div class="space-y-4">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <div class="flex items-center gap-3 p-4 border-b border-gray-700">
                            <span class="method-post px-2 py-1 rounded text-xs font-bold text-white">POST</span>
                            <code class="text-gray-300">/api/webhooks/ai/fault-prediction</code>
                            <span class="px-2 py-0.5 bg-green-500/20 text-green-400 rounded text-xs">Public</span>
                        </div>
                        <div class="p-4">
                            <pre class="bg-gray-900 p-3 rounded text-sm overflow-x-auto"><code class="language-json">{
  "panel_code": "PV-001",
  "fault_detected": true,
  "fault_type": "overheating",
  "confidence": 92.5,
  "severity": "high",
  "ai_analysis": {
    "root_cause": "High temperature detected",
    "contributing_factors": ["Low airflow", "Direct sunlight"]
  }
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t border-gray-700 text-center text-gray-400">
            <p>SOMA PV API Documentation v1.0.0</p>
            <p class="text-sm mt-2">For JSON documentation: <a href="/pv/pv-backend/public/api/docs" class="text-amber-400 hover:underline">/api/docs</a></p>
        </div>
    </div>
    <script>hljs.highlightAll();</script>
</body>
</html>
