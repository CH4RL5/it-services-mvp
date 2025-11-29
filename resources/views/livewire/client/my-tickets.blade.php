<div class="mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">📂 Mis Solicitudes Recientes</h3>

    @if($tickets->isEmpty())
    <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
        <p class="text-gray-500">Aún no tienes tickets. ¡Cuéntanos tu problema arriba! 👆</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($tickets as $ticket)
        <div
            class="bg-white p-4 rounded-lg shadow-sm border flex flex-col sm:flex-row justify-between items-center hover:shadow-md transition">

            {{-- Info del Ticket --}}
            <div class="flex-1 mb-4 sm:mb-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-bold text-gray-800">#{{ substr($ticket->uuid, 0, 8) }}</span>
                    <span class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-600">
                        {{ $ticket->category }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-gray-600 text-sm truncate max-w-md">
                    {{ $ticket->title }}
                </p>
            </div>

            {{-- Estado y Botón --}}
            <div class="flex items-center gap-4">
                @if($ticket->is_paid)
                <div class="text-right">
                    <span
                        class="block text-xs text-green-600 font-bold bg-green-100 px-2 py-1 rounded-full text-center">
                        ✅ Pagado
                    </span>
                    @if($ticket->expert_id)
                    <span class="text-xs text-blue-600 block mt-1">👨‍💻 Experto Asignado</span>
                    @else
                    <span class="text-xs text-gray-400 block mt-1">⏳ Buscando experto...</span>
                    @endif
                </div>

                <a href="{{ route('ticket.chat', $ticket->uuid) }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                    Ir al Chat 💬
                </a>
                @else
                <div class="text-right">
                    <span class="block text-xs text-red-600 font-bold bg-red-100 px-2 py-1 rounded-full text-center">
                        💳 Pago Pendiente
                    </span>
                </div>

                {{-- Reutilizamos la ruta del chat. Como no ha pagado, el ChatRoom le mostrará el bloqueo,
                idealmente aquí volveríamos a generar el link de Stripe, pero para MVP basta con enviarlo --}}
                <a href="{{ route('ticket.chat', $ticket->uuid) }}"
                    class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-900 transition">
                    Pagar Ahora
                </a>
                @endif
            </div>

        </div>
        @endforeach
    </div>
    @endif
</div>
