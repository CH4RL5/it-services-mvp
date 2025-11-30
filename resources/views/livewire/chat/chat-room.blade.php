<div class="max-w-4xl mx-auto py-6" wire:poll.2500ms="loadMessages">

    <div class="bg-white shadow rounded-lg overflow-hidden">

        {{-- CABECERA (HEADER) --}}
        <div class="bg-gray-100 p-4 border-b flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="font-bold text-lg">Ticket #{{ substr($ticket->uuid, 0, 8) }}</h2>
                    <span class="text-sm text-gray-500">{{ $ticket->category }}</span>
                </div>

                {{-- Etiquetas de Estado --}}
                @if($ticket->status === 'closed' || (is_object($ticket->status) && $ticket->status->value === 'closed'))
                <span class="bg-gray-800 text-white px-3 py-1 rounded-full text-xs font-bold uppercase">Cerrado</span>
                @elseif($ticket->is_paid)
                <span
                    class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Abierto</span>
                @else
                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Pago
                    Pendiente</span>
                @endif
            </div>

            {{-- BOTÓN FINALIZAR (Solo lo ve el Experto asignado si el ticket NO está cerrado) --}}
            @php
            $isClosed = $ticket->status === 'closed' || (is_object($ticket->status) && $ticket->status->value ===
            'closed');
            @endphp

            @if(!$isClosed && (auth()->id() === $ticket->expert_id || auth()->user()->role === 'admin')) <button
                wire:click="closeTicket"
                wire:confirm="¿Estás seguro de que el trabajo está terminado? Esto cerrará el chat."
                class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded shadow transition">
                🏁 Finalizar Trabajo
            </button>
            @endif
        </div>

        {{-- ÁREA DE MENSAJES --}}
        <div class="h-96 overflow-y-auto p-4 bg-gray-50 flex flex-col space-y-2" id="chat-box">
            @foreach($messages as $msg)
            <div class="flex {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                <div
                    class="{{ $msg->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800' }} rounded-lg px-4 py-2 max-w-xs">
                    {{-- SI TIENE FOTO, LA MOSTRAMOS --}}
                    @if($msg->attachment)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $msg->attachment) }}"
                            class="rounded-lg max-h-48 object-cover cursor-pointer hover:opacity-90 border border-gray-200"
                            onclick="window.open(this.src, '_blank')">
                    </div>
                    @endif
                    <p class="text-sm">{{ $msg->body }}</p>
                    <span class="text-xs opacity-75 block text-right mt-1">
                        {{ $msg->created_at->format('H:i') }}
                    </span>
                </div>
            </div>
            @endforeach

            @if($messages->isEmpty())
            <p class="text-center text-gray-400 mt-10">Inicio del chat. Esperando a un experto...</p>
            @endif
        </div>

        {{-- PIE DE PÁGINA (FOOTER) --}}
        <div class="p-4 bg-white border-t">

            {{-- CASO 1: TICKET CERRADO --}}
            @if($isClosed)
            <div
                class="flex flex-col items-center justify-center p-6 bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl">
                <div class="text-center">
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 mb-3 shadow-sm">
                        <span class="text-xl">🔒</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Este ticket ha finalizado</h3>
                    <p class="text-gray-500 text-sm mt-1">El experto ha marcado el trabajo como completado.</p>
                    <a href="{{ route('dashboard') }}"
                        class="inline-block mt-4 text-blue-600 font-bold hover:underline text-sm">Volver al Inicio</a>
                </div>
            </div>

            {{-- CASO 2: PAGO PENDIENTE --}}
            @elseif(!$ticket->is_paid)

            {{-- A) Si soy el CLIENTE: Botón de Pagar --}}
            @if(auth()->id() === $ticket->user_id)
            <div
                class="flex flex-col items-center justify-center py-6 bg-red-50 rounded-xl border border-red-100 shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-1">🔒 Chat Bloqueado</h3>
                <p class="text-gray-500 text-sm mb-4">Completa el pago para conectar con el experto.</p>
                <button wire:click="payNow" wire:loading.attr="disabled"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                    <span wire:loading.remove>💳 Pagar ${{ $ticket->amount }} MXN</span>
                    <span wire:loading>Procesando...</span>
                </button>
            </div>

            {{-- B) Si soy ADMIN o EXPERTO: Aviso de Espera (SIN INPUT) --}}
            @else
            <div
                class="flex items-center justify-center p-6 bg-yellow-50 border-2 border-dashed border-yellow-200 rounded-xl">
                <div class="text-center">
                    <span class="text-2xl block mb-2">⏳</span>
                    <h3 class="text-lg font-bold text-yellow-800">Esperando Pago del Cliente</h3>
                    <p class="text-yellow-700 text-sm mt-1">
                        El chat se habilitará automáticamente cuando el usuario complete el pago.
                    </p>
                </div>
            </div>
            @endif

            {{-- CASO 3: CHAT ACTIVO (Solo aquí se muestra el input) --}}
            @else
            <div class="flex flex-col w-full">
                {{-- Preview de Imagen --}}
                @if ($image)
                <div
                    class="flex items-center gap-2 p-2 bg-gray-100 rounded-t-lg mx-4 border border-b-0 border-gray-300">
                    <img src="{{ $image->temporaryUrl() }}"
                        class="h-16 w-16 object-cover rounded border border-gray-400">
                    <button wire:click="$set('image', null)"
                        class="text-red-500 hover:text-red-700 font-bold px-2">×</button>
                    <span class="text-xs text-gray-500">Imagen lista...</span>
                </div>
                @endif

                {{-- Formulario --}}
                <form wire:submit.prevent="sendMessage" class="flex items-center gap-3">
                    {{-- Botón Clip --}}
                    <div>
                        <input type="file" wire:model="image" id="file-upload" class="hidden" accept="image/*">
                        <label for="file-upload"
                            class="cursor-pointer text-gray-400 hover:text-blue-600 p-2 transition">
                            📎
                        </label>
                    </div>

                    <input type="text" wire:model="newMessage"
                        class="flex-1 border border-gray-300 rounded-full px-5 py-3 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"
                        placeholder="Escribe un mensaje...">

                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white rounded-full p-3 shadow-md transition transform hover:scale-105">
                        ➤
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
             var chatBox = document.getElementById('chat-box');
             chatBox.scrollTop = chatBox.scrollHeight;
        });

        // Auto-scroll cuando se envía un mensaje
        window.addEventListener('message-sent', event => {
             var chatBox = document.getElementById('chat-box');
             setTimeout(() => { chatBox.scrollTop = chatBox.scrollHeight; }, 100);
        });
    </script>
</div>
