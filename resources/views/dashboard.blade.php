<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{-- Título Cambiante --}}
            @if(auth()->user()->role === 'expert')
            👨‍💻 Panel de Control (Experto)
            @elseif(auth()->user()->role === 'admin')
            👮‍♂️ Administración
            @else
            🚀 Mis Solicitudes
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- LÓGICA DE ROLES --}}

            @if(auth()->user()->role === 'expert')
            {{-- VISTA DEL EXPERTO --}}
            <livewire:expert.ticket-list />
            @elseif(auth()->user()->role === 'admin')
            {{-- VISTA DEL ADMIN --}}
            <livewire:admin.dashboard />

            @else
            {{-- VISTA DEL CLIENTE (Por defecto) --}}
            {{-- VISTA DEL CLIENTE (Por defecto) --}}

            {{-- ZONA DE NOTIFICACIONES --}}
            @if(auth()->user()->unreadNotifications->isNotEmpty())
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 shadow rounded relative">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-blue-800 flex items-center gap-2">
                        🔔 Tienes novedades
                    </h3>
                    {{-- Botón para limpiar notificaciones --}}
                    <button wire:click="$refresh"
                        onclick="fetch('/notifications/mark-read', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })"
                        class="text-xs text-blue-600 hover:text-blue-800 underline">
                        Marcar todo como leído
                    </button>
                </div>

                <ul class="mt-2 space-y-2">
                    @foreach(auth()->user()->unreadNotifications as $notification)
                    <li class="bg-white p-3 rounded shadow-sm flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            {{ $notification->data['message'] }}
                        </span>
                        <a href="{{ route('ticket.chat', $notification->data['ticket_uuid']) }}"
                            class="bg-blue-600 text-white text-xs px-3 py-1 rounded hover:bg-blue-700 transition">
                            Ver Chat
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            {{-- FIN ZONA NOTIFICACIONES --}}
            <livewire:create-ticket />
            <livewire:client.my-tickets />

            @endif

        </div>
    </div>
</x-app-layout>
