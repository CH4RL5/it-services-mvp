<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Message;
use App\Events\MessageSent; // Asegúrate de importar esto
use Illuminate\Support\Facades\Auth;
use App\Services\StripeService;
use App\Models\User;
use App\Notifications\NewWorkOpportunity;
class ChatRoom extends Component
{
    public Ticket $ticket;
    public $newMessage = '';

    // NOTA: Eliminamos 'public $messages' para evitar el error de array_merge

    public function mount(Ticket $ticket)
    {
        // Seguridad: Permitir si es Dueño, Experto Asignado O Admin
        if (Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->expert_id && Auth::user()->role !== 'admin') {
            abort(403); // Acceso denegado
        }

        $this->ticket = $ticket;

        if (request()->has('payment') && request('payment') == 'success') {
            if (!$this->ticket->is_paid) {

                // 1. Activar Ticket
                $this->ticket->update(['is_paid' => true, 'status' => 'open']);

                // 2. MATCHMAKING AUTOMÁTICO (Notificar Expertos)
                // Buscamos expertos cuya especialidad coincida con la categoría del ticket
                $matchingExperts = User::where('role', 'expert')
                    ->where('expertise', $this->ticket->category)
                    ->get();

                foreach ($matchingExperts as $expert) {
                    $expert->notify(new NewWorkOpportunity($this->ticket));
                }
            }
        }

    }

    public function sendMessage()
    {
        $this->validate(['newMessage' => 'required']);

        $message = Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'body' => $this->newMessage,
        ]);

        // --- CAMBIO: Envolvemos en try-catch para ignorar errores de conexión ---
        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            // Si Reverb falla, no hacemos nada. El chat sigue funcionando por Polling.
        }
        // ------------------------------------------------------------------------

        $this->newMessage = '';
        // Esto refresca TU pantalla inmediatamente
        $this->dispatch('message-sent');
    }
    // Configuración de WebSockets
    public function getListeners()
    {
        return [
            // Cuando llegue un evento 'MessageSent', solo refrescamos la vista
            "echo:ticket.{$this->ticket->uuid},MessageSent" => '$refresh',
        ];
    }
    // --- AGREGAR ESTO ---
    public function loadMessages()
    {
        // No necesita código adentro.
        // Al llamarse, Livewire recarga el componente y
        // el método render() de abajo actualiza los mensajes automáticamente.
    }

    public function render()
    {
        return view('livewire.chat.chat-room', [
            // Pasamos los mensajes directo a la vista aquí.
            // Esto evita el error de tipos (Collection vs Array).
            'messages' => $this->ticket->messages()->with('user')->get()
        ])->layout('layouts.app');
    }
    public function payNow(StripeService $stripe)
    {
        // Generamos un nuevo link de pago para este ticket
        $checkoutUrl = $stripe->createCheckoutSession($this->ticket);

        // Redirigimos a Stripe
        return redirect($checkoutUrl);
    }
    public function closeTicket()
    {
        // Solo el experto asignado o el admin pueden cerrar
        if (Auth::id() !== $this->ticket->expert_id && Auth::user()->role !== 'admin') {
            return;
        }

        $this->ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Opcional: Mandar un mensaje automático de despedida
        Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => null, // Mensaje del sistema
            'body' => '🔒 El experto ha marcado este ticket como FINALIZADO.',
        ]);

        $this->dispatch('message-sent'); // Refrescar chat
    }
}
