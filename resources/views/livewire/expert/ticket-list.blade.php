<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 " wire:poll.5s>

    {{-- LISTA DE TICKETS DISPONIBLES --}}
    <div class="mb-10">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">🔥 Tickets Disponibles</h2>

        @if($availableTickets->isEmpty())
        <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded text-gray-500">
            No hay tickets pendientes. ¡Buen trabajo! ☕
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($availableTickets as $ticket)
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
                <div class="flex justify-between items-start mb-2">
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full uppercase font-bold">
                        {{ $ticket->category }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
                {{--NOMBRE DEL CLIENTE --}}
                <p class="text-xs font-bold text-gray-500 mb-1 flex items-center gap-1">
                    👤 {{ $ticket->user->name }}
                    @if($ticket->user->phone) <span class="text-green-600">(WhatsApp)</span> @endif
                </p>
                <h3 class="font-bold text-lg mb-2">{{ Str::limit($ticket->title, 40) }}</h3>

                <div class="flex justify-between items-center mt-4">
                    <span class="font-bold text-gray-800">${{ $ticket->amount }} MXN</span>
                    {{-- CAMBIO: Botón ahora solo ve detalles --}}
                    <button wire:click="viewDetails('{{ $ticket->uuid }}')"
                        class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded text-sm transition">
                        🔍 Ver y Decidir
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- MODAL DE DECISIÓN --}}
    @if($showModal && $selectedTicket)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Fondo oscuro --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="rejectTicket"></div>

            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Ticket: {{ $selectedTicket->category }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-2">
                                    <strong>Cliente:</strong> {{ $selectedTicket->user->name }}
                                </p>
                                <p class="text-sm text-gray-500 mb-4 bg-gray-50 p-3 rounded border">
                                    {{ $selectedTicket->description }}
                                </p>
                                <p class="text-green-600 font-bold">
                                    Pago confirmado: ${{ $selectedTicket->amount }} MXN
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- BOTONES ACEPTAR / RECHAZAR --}}
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="takeTicket"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        ✅ Aceptar Trabajo
                    </button>
                    <button type="button" wire:click="rejectTicket"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        ❌ Rechazar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- LISTA DE MIS CASOS (Igual que antes) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- COLUMNA 1: CASOS ACTIVOS (Prioridad Alta) --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                🟢 En Curso <span class="text-sm font-normal text-gray-500">({{ $activeTickets->count() }})</span>
            </h2>

            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-blue-100">
                @if($activeTickets->isEmpty())
                <div class="p-8 text-center text-gray-400">
                    No tienes casos activos. ¡Toma uno de arriba! 👆
                </div>
                @else
                <ul class="divide-y divide-gray-100">
                    @foreach($activeTickets as $ticket)
                    <li class="hover:bg-blue-50 transition">
                        <a href="{{ route('ticket.chat', $ticket->uuid) }}" class="block px-6 py-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded uppercase">
                                    {{ $ticket->category }}
                                </span>
                                <span class="text-xs text-gray-400 font-mono">#{{ substr($ticket->uuid, 0, 6) }}</span>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800 mb-1">{{ Str::limit($ticket->title, 40) }}</h4>
                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                👤 {{ $ticket->user->name }}
                                <span
                                    class="text-xs text-green-600 font-bold bg-green-50 px-1 rounded ml-2">Pagado</span>
                            </p>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

        {{-- COLUMNA 2: HISTORIAL (Finalizados) --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-600 mb-4 flex items-center gap-2">
                🏁 Finalizados <span class="text-sm font-normal text-gray-400">({{ $closedTickets->count() }})</span>
            </h2>

            <div
                class="bg-gray-50 shadow rounded-lg overflow-hidden border border-gray-200 opacity-80 hover:opacity-100 transition">
                @if($closedTickets->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">
                    Aún no has finalizado ningún ticket.
                </div>
                @else
                <ul class="divide-y divide-gray-200">
                    @foreach($closedTickets as $ticket)
                    <li class="hover:bg-white transition">
                        <a href="{{ route('ticket.chat', $ticket->uuid) }}" class="block px-6 py-4">
                            <div class="flex justify-between items-center">
                                <p class="text-sm font-medium text-gray-600 line-through">
                                    {{ Str::limit($ticket->title, 45) }}
                                </p>
                                <span class="text-xs text-gray-400">
                                    {{ $ticket->updated_at->format('d M') }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="text-xs text-gray-400">#{{ substr($ticket->uuid, 0, 6) }}</span>
                                <span
                                    class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">Cerrado</span>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

    </div>
</div>
