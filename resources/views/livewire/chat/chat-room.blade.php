<div class="max-w-4xl mx-auto py-6" wire:poll.2500ms="loadMessages">

    <div class="bg-white shadow rounded-lg overflow-hidden">

        {{-- CABECERA (HEADER) --}}
        <div class="bg-gray-100 p-4 border-b flex justify-between items-center sticky top-0 z-10">
            <div class="flex items-center gap-3">
                {{-- Avatar --}}
                <div
                    class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm border border-blue-200">
                    {{ substr($ticket->user->name, 0, 2) }}
                </div>
                <div class="flex flex-col">
                    <h2 class="font-bold text-gray-800 text-sm sm:text-base flex items-center gap-2">
                        {{ $ticket->user->name }}
                        @if($ticket->user->phone) <span
                            class="bg-green-100 text-green-700 text-[10px] px-1.5 py-0.5 rounded border border-green-200">WA</span>
                        @endif
                    </h2>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="font-mono bg-gray-200 px-1 rounded">#{{ substr($ticket->uuid, 0, 6) }}</span>
                        <span>•</span>
                        <span>{{ $ticket->category }}</span>
                    </div>
                </div>
            </div>

            {{-- Estado / Botón Finalizar --}}
            <div class="flex items-center gap-3">
                {{-- Etiqueta de Estado --}}
                @if($ticket->status === 'closed' || (is_object($ticket->status) && $ticket->status->value === 'closed'))
                <span class="bg-gray-800 text-white px-3 py-1 rounded-full text-xs font-bold uppercase">Cerrado</span>
                @elseif($ticket->is_paid)
                <span
                    class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Abierto</span>
                @else
                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Sin
                    Pagar</span>
                @endif

                {{-- Botón Finalizar --}}
                @php $isClosed = $ticket->status === 'closed' || (is_object($ticket->status) && $ticket->status->value
                === 'closed'); @endphp
                @if(!$isClosed && (auth()->id() === $ticket->expert_id || auth()->user()->role === 'admin'))
                <button wire:click="closeTicket" wire:confirm="¿Finalizar atención? Esto cerrará el chat."
                    class="bg-red-50 text-red-600 border border-red-200 hover:bg-red-600 hover:text-white text-xs font-bold py-2 px-3 rounded transition">
                    🏁 Finalizar
                </button>
                @endif
            </div>
        </div>

        {{-- ÁREA DE MENSAJES --}}
        <div class="h-96 overflow-y-auto p-4 bg-gray-50 flex flex-col space-y-2" id="chat-box">
            @foreach($messages as $msg)
            <div class="flex {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }} group">
                <div
                    class="{{ $msg->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-white text-gray-800 border border-gray-200' }} rounded-lg px-4 py-2 max-w-xs shadow-sm relative">

                    {{-- Imagen --}}
                    @if($msg->attachment)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $msg->attachment) }}"
                            class="rounded-lg max-h-48 object-cover cursor-pointer hover:opacity-90 bg-black"
                            onclick="window.open(this.src, '_blank')">
                    </div>
                    @endif

                    {{-- Texto --}}
                    <p class="text-sm">{!! nl2br(e($msg->body)) !!}</p>

                    {{-- Hora --}}
                    <span class="text-[10px] opacity-70 block text-right mt-1">
                        {{ $msg->created_at->format('H:i') }}
                    </span>

                    {{-- Botón Censura --}}
                    @if(auth()->user()->role !== 'client' && !str_contains($msg->body, '🔒'))
                    <button wire:click="redactMessage({{ $msg->id }})" wire:confirm="¿Borrar dato sensible?"
                        class="absolute -top-2 -right-2 bg-gray-700 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition transform hover:scale-110 shadow"
                        title="Censurar">
                        🔒
                    </button>
                    @endif
                </div>
            </div>
            @endforeach

            @if($messages->isEmpty())
            <div class="flex h-full items-center justify-center text-gray-400 text-sm flex-col">
                <span class="text-3xl mb-2">👋</span>
                <p>Inicio del chat. Esperando respuesta...</p>
            </div>
            @endif
        </div>

        {{-- FOOTER (LÓGICA BLINDADA) --}}
        <div class="p-4 bg-white border-t">

            {{-- 1. SI ESTÁ CERRADO --}}
            @if($isClosed)
            <div
                class="flex flex-col items-center justify-center p-6 bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl">
                <div class="text-center mb-4">
                    <span class="text-2xl block mb-2">🔒</span>
                    <h3 class="text-lg font-bold text-gray-800">Ticket Finalizado</h3>
                    <p class="text-gray-500 text-sm">El experto ha cerrado este caso.</p>
                </div>

                {{-- Calificación --}}
                @if($ticket->rating)
                <div class="text-yellow-400 text-2xl tracking-widest">{{ str_repeat('★', $ticket->rating) }}</div>
                @elseif(auth()->id() === $ticket->user_id)
                <div class="w-full max-w-xs">
                    <p class="text-center text-sm font-bold text-yellow-700 mb-2">Califica la atención:</p>
                    <div class="flex justify-center gap-2 mb-3">
                        @foreach(range(1,5) as $star)
                        <button wire:click="$set('rating', {{ $star }})"
                            class="text-3xl {{ $rating >= $star ? 'text-yellow-400' : 'text-gray-300' }} hover:scale-110 transition">★</button>
                        @endforeach
                    </div>
                    <button wire:click="rateService"
                        class="w-full bg-yellow-500 text-white font-bold py-2 rounded shadow hover:bg-yellow-600">Enviar</button>
                </div>
                @endif

                <a href="{{ route('dashboard') }}" class="mt-6 text-blue-600 hover:underline text-sm font-bold">Volver
                    al Inicio</a>
            </div>

            {{-- 2. SI FALTA PAGO (Y SOY CLIENTE) --}}
            @elseif(!$ticket->is_paid && auth()->id() === $ticket->user_id)
            <div class="flex flex-col items-center justify-center py-6 bg-red-50 rounded-xl border border-red-100">
                <h3 class="font-bold text-gray-800">🔒 Chat Bloqueado</h3>
                <p class="text-gray-500 text-sm mb-4">Paga para activar el servicio.</p>
                <button wire:click="payNow"
                    class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full shadow hover:bg-blue-700 transition">
                    💳 Pagar ${{ $ticket->amount }} MXN
                </button>
            </div>

            {{-- 3. SI FALTA PAGO (Y SOY EXPERTO/ADMIN) --}}
            @elseif(!$ticket->is_paid)
            <div
                class="flex items-center justify-center p-6 bg-yellow-50 border-2 border-dashed border-yellow-200 rounded-xl text-center">
                <div>
                    <span class="text-2xl block mb-1">⏳</span>
                    <h3 class="font-bold text-yellow-800">Esperando Pago</h3>
                    <p class="text-yellow-700 text-xs">El cliente aún no paga.</p>
                </div>
            </div>

            {{-- 4. CHAT ACTIVO (Solo si pagado y abierto) --}}
            @else
            <div class="flex flex-col w-full">
                {{-- Preview Imagen --}}
                @if ($image)
                <div
                    class="flex items-center gap-2 p-2 bg-gray-100 rounded-t-lg mx-4 border border-b-0 border-gray-300">
                    <img src="{{ $image->temporaryUrl() }}" class="h-12 w-12 object-cover rounded">
                    <button wire:click="$set('image', null)" class="text-red-500 font-bold px-2">×</button>
                    <span class="text-xs text-gray-500">Imagen lista...</span>
                </div>
                @endif

                <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                    {{-- Clip --}}
                    <div>
                        <input type="file" wire:model="image" id="file-upload" class="hidden" accept="image/*">
                        <label for="file-upload"
                            class="cursor-pointer p-2 text-gray-400 hover:text-blue-600 transition">📎</label>
                    </div>

                    <input type="text" wire:model="newMessage"
                        class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        placeholder="Escribe un mensaje...">

                    <button type="submit"
                        class="bg-blue-600 text-white rounded-full p-2.5 shadow hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <script>
        window.addEventListener('message-sent', event => {
             var chatBox = document.getElementById('chat-box');
             setTimeout(() => { chatBox.scrollTop = chatBox.scrollHeight; }, 100);
        });
    </script>
    {{-- ZONA DE DISPUTA --}}
    <div class="mt-4 text-center">
        @if($ticket->is_disputed)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative inline-block">
            <strong class="font-bold">⚠️ En Revisión:</strong>
            <span class="block sm:inline">Has reportado este ticket. Un administrador te contactará pronto.</span>
        </div>
        @elseif($ticket->is_paid)
        {{-- Solo mostrar si ya pagó (nadie reclama algo gratis) --}}
        <button wire:click="reportIssue"
            wire:confirm="¿Deseas reportar un problema con este servicio? El administrador revisará el caso."
            class="text-xs text-gray-400 hover:text-red-500 underline transition">
            ¿Tuviste un problema con el pago o el experto? Reportar aquí.
        </button>
        @endif
    </div>
</div>
