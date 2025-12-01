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
            {{-- Componente Vivo de Notificaciones --}}
            <livewire:notifications />
            <livewire:create-ticket />
            <livewire:client.my-tickets />

            @endif

        </div>


    </div>
</x-app-layout>