<div class="py-6">
    {{-- 1. Tarjetas de Métricas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="text-gray-500 text-sm font-bold uppercase">Ingresos Totales</div>
            <div class="text-3xl font-bold text-gray-800 mt-2">${{ number_format($totalRevenue, 2) }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="text-gray-500 text-sm font-bold uppercase">Tickets Creados</div>
            <div class="text-3xl font-bold text-gray-800 mt-2">{{ $totalTickets }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="text-gray-500 text-sm font-bold uppercase">Expertos Activos</div>
            <div class="text-3xl font-bold text-gray-800 mt-2">{{ $activeExperts }}</div>
        </div>
    </div>

    {{-- 2. Tabla de Auditoría --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-bold text-gray-800">📋 Auditoría de Tickets</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID /
                        Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Problema
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experto
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($tickets as $ticket)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        #{{ substr($ticket->uuid, 0, 6) }} <br>
                        <span class="text-xs">{{ $ticket->created_at->format('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $ticket->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $ticket->user->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 mb-1">
                            {{ $ticket->category }}
                        </span>
                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $ticket->title }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($ticket->expert)
                        {{ $ticket->expert->name }}
                        @else
                        <span class="text-yellow-500 italic">-- Pendiente --</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('ticket.chat', $ticket->uuid) }}"
                            class="text-indigo-600 hover:text-indigo-900">Ver Chat</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
