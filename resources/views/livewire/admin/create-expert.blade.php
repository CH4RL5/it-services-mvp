<div>
    {{-- 1. FORMULARIO DE CREACIÓN --}}
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mb-8">
        <h3 class="text-lg font-bold mb-4 text-gray-800 flex items-center gap-2">
            <span class="bg-blue-100 text-blue-600 p-1 rounded">👷‍♂️</span>
            Dar de Alta Nuevo Experto
        </h3>

        @if (session()->has('message'))
        <div
            class="p-3 mb-4 bg-green-100 text-green-700 rounded border border-green-200 flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button wire:click="$set('message', null)" class="text-green-800 font-bold">×</button>
        </div>
        @endif

        <form wire:submit.prevent="create" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Nombre</label>
                <input type="text" wire:model="name"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Email</label>
                <input type="email" wire:model="email"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Teléfono (WhatsApp)</label>
                <input type="text" wire:model="phone"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Ej: 521...">
            </div>

            <div>
                <label class="block text-sm font-bold mb-1 text-gray-700">Especialidad</label>
                <select wire:model="expertise"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecciona...</option>
                    <option value="DNS">DNS y Dominios</option>
                    <option value="Servidores">Servidores y VPS</option>
                    <option value="Correo">Correo Electrónico</option>
                    <option value="Hosting">Hosting General</option>
                    <option value="Otros">Otros / General</option>
                </select>
                @error('expertise') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <button type="submit"
                    class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900 w-full transition font-bold shadow">
                    Crear Experto
                </button>
                <p class="text-xs text-gray-500 mt-2 text-center">La contraseña por defecto será:
                    <strong>expert123</strong></p>
            </div>
        </form>
    </div>

    {{-- 2. LISTA DE EXPERTOS (NUEVA SECCIÓN) --}}
    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800">📋 Equipo de Expertos Activos</h3>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre /
                        Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Especialidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($experts as $expert)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $expert->name }}</div>
                        <div class="text-sm text-gray-500">{{ $expert->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $expert->expertise ?? 'General' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $expert->phone ?? '--' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="delete({{ $expert->id }})"
                            wire:confirm="¿Seguro que quieres eliminar a este experto?"
                            class="text-red-600 hover:text-red-900 font-bold bg-red-50 px-3 py-1 rounded hover:bg-red-100 transition">
                            Eliminar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">
                        No hay expertos registrados aún. Usa el formulario de arriba. 👆
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
