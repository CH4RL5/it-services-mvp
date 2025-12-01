<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Mimic IT</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased font-sans">
    <div
        class="min-h-screen bg-gray-50 text-black/50 dark:bg-black dark:text-white/50 selection:bg-[#FF2D20] selection:text-white">
        <div
            class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">

                {{-- Header / Nav --}}
                <header class="flex items-center justify-between py-10">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <span class="text-3xl font-black text-gray-900 tracking-tighter flex items-center gap-2">
                            {{-- Icono inline --}}
                            <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Mimic<span class="text-blue-600">IT</span>
                        </span>
                    </div>
                    <nav class="flex flex-1 justify-end gap-4">
                        @auth
                        <a href="{{ url('/dashboard') }}"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]">
                            Ir al Panel
                        </a>
                        @else
                        <a href="{{ route('login') }}"
                            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20]">
                            Entrar
                        </a>
                        <a href="{{ route('register') }}"
                            class="rounded-md bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700 focus:outline-none">
                            Registrarse
                        </a>
                        @endauth
                    </nav>
                </header>

                {{-- Hero Content --}}
                <main class="mt-16 text-center">
                    <h1 class="text-5xl font-extrabold text-gray-900 tracking-tight sm:text-7xl mb-6">
                        Soporte Técnico <br>
                        <span class="text-blue-600">Al Instante.</span>
                    </h1>
                    <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto mb-10">
                        Olvídate de los tickets eternos. Conecta con expertos certificados en segundos a través de
                        nuestro
                        chat inteligente o WhatsApp.
                    </p>

                    <div class="flex justify-center gap-4">
                        @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-8 py-4 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1">
                            🚀 Crear Nuevo Ticket
                        </a>
                        @else
                        <a href="{{ route('register') }}"
                            class="px-8 py-4 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1">
                            Empezar Ahora
                        </a>
                        @endauth
                    </div>

                    {{-- Feature Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-20 text-left">
                        <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="text-3xl mb-4">🤖</div>
                            <h3 class="font-bold text-lg text-gray-900">IA Integrada</h3>
                            <p class="text-sm text-gray-500 mt-2">Nuestra IA diagnostica y clasifica tu problema antes
                                de
                                que hables con un humano.</p>
                        </div>
                        <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="text-3xl mb-4">💬</div>
                            <h3 class="font-bold text-lg text-gray-900">WhatsApp Nativo</h3>
                            <p class="text-sm text-gray-500 mt-2">Gestiona todo desde tu celular. Recibe alertas y
                                chatea
                                sin entrar a la web.</p>
                        </div>
                        <div class="p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
                            <div class="text-3xl mb-4">🛡️</div>
                            <h3 class="font-bold text-lg text-gray-900">Pago Seguro</h3>
                            <p class="text-sm text-gray-500 mt-2">Tu dinero está protegido por Stripe hasta que el
                                servicio
                                inicia.</p>
                        </div>
                    </div>
                </main>

                <footer class="py-16 text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Mimic MVP. Construido con Laravel 12 & Livewire.
                </footer>
            </div>
        </div>
    </div>
</body>

</html>