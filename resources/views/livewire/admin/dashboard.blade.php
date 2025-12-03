<div class="py-6 space-y-8">

    {{-- 1. TARJETAS DE MÉTRICAS (KPIs) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Ingresos Totales</div>
                <div class="text-3xl font-black text-gray-800 mt-1">${{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="p-3 bg-green-50 rounded-full text-green-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Tickets Totales</div>
                <div class="text-3xl font-black text-gray-800 mt-1">{{ $totalTickets }}</div>
            </div>
            <div class="p-3 bg-blue-50 rounded-full text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500 flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-xs font-bold uppercase tracking-wider">Expertos Activos</div>
                <div class="text-3xl font-black text-gray-800 mt-1">{{ $activeExperts }}</div>
            </div>
            <div class="p-3 bg-purple-50 rounded-full text-purple-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
            </div>
        </div>
    </div>

    {{-- 2. GRÁFICAS DE ANÁLISIS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-700 mb-4">📈 Tendencia de Ingresos (7 días)</h3>
            <div class="h-64">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-700 mb-4">🍰 Distribución de Problemas</h3>
            <div class="h-64 flex justify-center">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    {{-- 3. TABLA DE AUDITORÍA (La que ya tenías mejorada) --}}
    <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">📋 Auditoría Reciente</h3>
            <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded">Últimos 10</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ticket
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Problema</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Experto
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ver
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                    <tr
                        class="{{ $ticket->is_disputed ? 'bg-red-50 border-l-4 border-red-500' : 'hover:bg-gray-50 transition' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="font-mono text-gray-700 font-bold">#{{ substr($ticket->uuid, 0, 6) }}</span>
                            <br>
                            @if($ticket->is_disputed)
                            <div class="group relative inline-block">
                                <span
                                    class="bg-red-600 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase animate-pulse cursor-help">
                                    🚨 RECLAMO
                                </span>
                                <div
                                    class="absolute bottom-full left-0 mb-2 hidden w-48 p-2 bg-black text-white text-xs rounded group-hover:block z-50 shadow-lg">
                                    {{ $ticket->dispute_reason }}
                                </div>
                            </div>
                            @endif
                            <span class="text-[10px]">{{ $ticket->created_at->format('d M') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ $ticket->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $ticket->user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 mb-1">
                                {{ $ticket->category }}
                            </span>
                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ Str::limit($ticket->title, 25) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($ticket->expert)
                            <span class="text-green-700 font-bold">✓ {{ $ticket->expert->name }}</span>
                            @else
                            <span class="text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded text-xs">Pendiente</span>
                            @endif
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end items-center gap-2">

                            {{-- LÓGICA DE RESOLUCIÓN --}}
                            @if($ticket->is_disputed)
                            {{-- Botón para abrir el Modal (si usaste el método del modal) --}}
                            <button wire:click="openResolutionModal({{ $ticket->id }})"
                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded shadow text-xs font-bold transition flex items-center gap-1"
                                title="Resolver Disputa">
                                <span>⚖️</span> Resolver
                            </button>
                            @endif

                            {{-- Enlace al Chat --}}
                            <a href="{{ route('ticket.chat', $ticket->uuid) }}"
                                class="text-indigo-600 hover:text-indigo-900 font-bold border border-indigo-200 px-3 py-1 rounded hover:bg-indigo-50 transition">
                                Ver Chat →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50">
            {{ $tickets->links() }}
        </div>
    </div>

    {{-- SCRIPTS PARA GRÁFICAS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Datos desde PHP
            const incomeLabels = @json($chartIncomeLabels);
            const incomeData = @json($chartIncomeValues);
            const catLabels = @json($chartCatLabels);
            const catData = @json($chartCatValues);

            // Gráfica de Barras (Ingresos)
            new Chart(document.getElementById('incomeChart'), {
                type: 'bar',
                data: {
                    labels: incomeLabels,
                    datasets: [{
                        label: 'Ingresos ($ MXN)',
                        data: incomeData,
                        backgroundColor: '#3B82F6',
                        borderRadius: 5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Gráfica de Pastel (Categorías)
            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catData,
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6366F1', '#8B5CF6']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
</div>
