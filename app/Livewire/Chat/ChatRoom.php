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
use Livewire\WithFileUploads;
class ChatRoom extends Component
{
    use WithFileUploads;
    public Ticket $ticket;
    public $newMessage = '';
    public $rating = 5; // Default 5 estrellas
    public $review = '';
    public $image;

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
        // 1. VALIDACIÓN CORRECTA: Texto O Imagen (uno de los dos, o ambos)
        // Quitamos la línea de validate individual que tenías antes
        $this->validate([
            'newMessage' => 'required_without:image',
            'image' => 'nullable|image|max:10240', // Subimos límite a 10MB
        ]);

        // 2. GUARDAR IMAGEN EN DISCO
        $imagePath = null;
        if ($this->image) {
            // Guardamos en 'storage/app/public/chat-images'
            $imagePath = $this->image->store('chat-images', 'public');
        }

        // 3. CREAR MENSAJE EN BASE DE DATOS
        $message = Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            // Si no hay texto, ponemos un placeholder
            'body' => $this->newMessage ?? '📷 Imagen adjunta',
            // ¡ESTA LÍNEA FALTABA! Sin esto, la imagen no se guarda en la BD
            'attachment' => $imagePath,
        ]);

        // 4. BROADCAST (WebSockets)
        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            // Si falla Reverb, no pasa nada, seguimos con Polling
        }

        // 5. LIMPIEZA
        // Reseteamos tanto el texto COMO la imagen temporal
        $this->reset(['newMessage', 'image']);

        // Avisamos al front para hacer scroll
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
    public function rateService()
    {
        // Solo el dueño puede calificar
        if (Auth::id() !== $this->ticket->user_id) return;

        $this->ticket->update([
            'rating' => $this->rating,
            'review' => $this->review
        ]);

        // Mensaje de agradecimiento
        session()->flash('success', '¡Gracias por tu opinión!');
    }
}
