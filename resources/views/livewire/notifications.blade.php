<div>
    @if($notifications->isNotEmpty())
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 shadow rounded relative transition-all duration-500">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-blue-800 flex items-center gap-2">
                    🔔 Tienes novedades
                </h3>

                {{-- ESTE BOTÓN AHORA SÍ FUNCIONA --}}
                <button
                    wire:click="markAsRead"
                    class="text-xs bg-blue-200 text-blue-800 px-3 py-1 rounded hover:bg-blue-300 transition"
                >
                    Marcar todo como leído
                </button>
            </div>

            <ul class="mt-2 space-y-2">
                @foreach($notifications as $notification)
                    <li class="bg-white p-3 rounded shadow-sm flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            {{ $notification->data['message'] ?? 'Nueva notificación' }}
                        </span>
                        <a href="{{ route('ticket.chat', $notification->data['ticket_uuid']) }}" class="bg-blue-600 text-white text-xs px-3 py-1 rounded hover:bg-blue-700 transition">
                            Ver Chat
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
