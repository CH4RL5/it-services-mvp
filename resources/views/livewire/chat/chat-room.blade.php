<div class="max-w-4xl mx-auto py-6" wire:poll.2500ms="loadMessages">
    {{-- wire:poll es un truco temporal para simular tiempo real si Reverb falla --}}

    <div class="bg-white shadow rounded-lg overflow-hidden">
        {{-- Cabecera --}}
        <div class="bg-gray-100 p-4 border-b flex justify-between items-center">
            <div>
                <h2 class="font-bold text-lg">Ticket #{{ substr($ticket->uuid, 0, 8) }}</h2>
                <span class="text-sm text-gray-500">{{ $ticket->category }}</span>
            </div>
            <div class="text-sm">
                @if($ticket->is_paid)
                <span class="bg-green-200 text-green-800 px-2 py-1 rounded">Pagado</span>
                @else
                <span class="bg-red-200 text-red-800 px-2 py-1 rounded">Pago Pendiente</span>
                @endif
            </div>
        </div>

        {{-- Área de Mensajes --}}
        <div class="h-96 overflow-y-auto p-4 bg-gray-50 flex flex-col space-y-2" id="chat-box">
            @foreach($messages as $msg)
            <div class="flex {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                <div
                    class="{{ $msg->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-800' }} rounded-lg px-4 py-2 max-w-xs">
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

        {{-- Input --}}
        <div class="p-4 bg-white border-t">
            @if($ticket->is_paid)
            <form wire:submit.prevent="sendMessage" class="flex gap-2">
                <input type="text" wire:model="newMessage" class="flex-1 border rounded-lg px-4 py-2"
                    placeholder="Escribe un mensaje...">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Enviar
                </button>
            </form>
            @else
            <div
                class="flex flex-col items-center justify-center w-full py-4 bg-red-50 rounded-lg border border-red-100">
                <p class="text-red-600 font-medium mb-3">
                    🔒 Este chat está bloqueado hasta que se complete el pago.
                </p>

                {{-- Botón que llama a la función que acabamos de crear --}}
                <button wire:click="payNow" wire:loading.attr="disabled"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                    <span wire:loading.remove>💳 Pagar ${{ $ticket->amount }} MXN con Stripe</span>
                    <span wire:loading>Redirigiendo...</span>
                </button>
            </div>
            @endif
        </div>
    </div>

    <script>
        // Auto-scroll al fondo
        document.addEventListener('livewire:initialized', () => {
             var chatBox = document.getElementById('chat-box');
             chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
</div>
