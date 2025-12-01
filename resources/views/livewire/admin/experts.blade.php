<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            👮‍♂️ Gestión de Personal
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6">
                <p class="text-gray-600">
                    Aquí puedes dar de alta nuevos expertos para que atiendan los tickets de la plataforma.
                </p>
            </div>

            {{-- Aquí cargamos el componente que ya creaste --}}
            <livewire:admin.create-expert />

        </div>
    </div>
</x-app-layout>