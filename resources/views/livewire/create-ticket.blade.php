<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md mt-10">
    <h2 class="text-2xl font-bold mb-4 text-gray-800">¿En qué podemos ayudarte hoy?</h2>

    {{-- Mensaje de éxito o redirección --}}
    @if (session()->has('message'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Describe tu problema</label>
            <textarea
                wire:model="description"
                class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                rows="4"
                placeholder="Ej: Mi correo no funciona, necesito configurar un dominio, el servidor se cayó..."
            ></textarea>
            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Botón con estado de carga --}}
        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition disabled:opacity-50 flex justify-center items-center"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove>🚀 Crear Ticket y Buscar Experto</span>
            <span wire:loading>🤖 Analizando con IA...</span>
        </button>
    </form>
</div>
