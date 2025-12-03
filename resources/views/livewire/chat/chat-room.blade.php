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

        {{-- PIE DE PÁGINA (FOOTER INTEGRADO) --}}
        <div class="bg-white border-t">

            {{-- A. BARRA DE ALERTA DE DISPUTA (Si existe) --}}
            @if($ticket->is_disputed)
            <div class="bg-red-50 px-4 py-3 border-b border-red-100 flex items-center justify-between animate-pulse">
                <div class="flex items-center gap-2 text-red-700 text-sm font-bold">
                    <span class="text-xl">🚨</span>
                    <span>Reclamo Abierto: "{{ Str::limit($ticket->dispute_reason, 50) }}"</span>
                </div>
                @if(auth()->user()->role === 'admin')
                <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded">Admin: Resolver en Dashboard</span>
                @else
                <span class="text-xs text-red-500">En revisión...</span>
                @endif
            </div>
            @endif

            <div class="p-4">
                {{-- B. LÓGICA DE ESTADO --}}

                {{-- 1. CASO TICKET CERRADO (Bloqueo Total) --}}
                @if($isClosed)
                <div
                    class="flex flex-col items-center justify-center p-6 bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl">
                    <div class="text-center mb-4">
                        <span class="text-2xl block mb-2">🔒</span>
                        <h3 class="text-lg font-bold text-gray-800">Ticket Finalizado</h3>
                        <p class="text-gray-500 text-sm">El caso ha sido cerrado.</p>
                    </div>

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

                    <a href="{{ route('dashboard') }}"
                        class="mt-4 text-blue-600 hover:underline text-sm font-bold">Volver al
                        Inicio</a>
                </div>

                {{-- 2. CASO FALTA PAGO (Bloqueo Parcial) --}}
                @elseif(!$ticket->is_paid)
                @if(auth()->id() === $ticket->user_id)
                <div class="flex flex-col items-center justify-center py-6 bg-red-50 rounded-xl border border-red-100">
                    <h3 class="font-bold text-gray-800">🔒 Chat Bloqueado</h3>
                    <p class="text-gray-500 text-sm mb-4">Paga para activar el servicio.</p>
                    <button wire:click="payNow"
                        class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full shadow hover:bg-blue-700 transition">
                        💳 Pagar ${{ $ticket->amount }} MXN
                    </button>
                </div>
                @else
                <div
                    class="flex items-center justify-center p-6 bg-yellow-50 border-2 border-dashed border-yellow-200 rounded-xl text-center">
                    <span class="text-2xl mr-2">⏳</span> <span class="font-bold text-yellow-800">Esperando Pago</span>
                </div>
                @endif

                {{-- 3. CASO CHAT ACTIVO (Normal o Disputa) --}}
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
                            ➤
                        </button>
                    </form>
                </div>
                @endif
            </div>

            {{-- 4. BOTÓN DE RECLAMO (Solo si no hay disputa activa y está pagado) --}}
            @if(!$ticket->is_disputed && $ticket->is_paid && !$isClosed && auth()->id() === $ticket->user_id)
            <div class="text-center pb-2 bg-gray-50 border-t border-gray-100">
                @if($showDisputeForm)
                <div class="p-4 bg-white shadow-inner">
                    <textarea wire:model="disputeReasonText" class="w-full border-gray-300 rounded text-sm mb-2"
                        rows="2" placeholder="Describe el problema..."></textarea>
                    <div class="flex justify-center gap-2">
                        <button wire:click="$set('showDisputeForm', false)"
                            class="text-gray-500 text-xs hover:underline">Cancelar</button>
                        <button wire:click="saveDispute"
                            class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded hover:bg-red-700">Enviar
                            Reporte</button>
                    </div>
                    @error('disputeReasonText') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                @else
                <button wire:click="$set('showDisputeForm', true)"
                    class="text-[10px] text-gray-400 hover:text-red-500 underline py-1">
                    ¿Problemas? Reportar aquí
                </button>
                @endif
            </div>
            @endif

        </div>

        <script>
            window.addEventListener('message-sent', event => {
             var chatBox = document.getElementById('chat-box');
             setTimeout(() => { chatBox.scrollTop = chatBox.scrollHeight; }, 100);
        });
        </script>

    </div>
