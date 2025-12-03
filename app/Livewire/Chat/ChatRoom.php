<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Ticket;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use App\Services\StripeService;
use App\Services\WhatsAppService; // Importante
use App\Notifications\NewWorkOpportunity;

class ChatRoom extends Component
{
    use WithFileUploads;

    public Ticket $ticket;
    public $newMessage = '';
    public $image;

    // Variables para calificación
    public $rating = 5;
    public $review = '';
    public $disputeReasonText = ''; // El texto que escribe el usuario
    public $showDisputeForm = false; // Para mostrar/ocultar el input
    public function mount(Ticket $ticket)
    {
        // 1. Seguridad: Solo Dueño, Experto o Admin
        if (Auth::id() !== $ticket->user_id && Auth::id() !== $ticket->expert_id && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $this->ticket = $ticket;

        // 2. Lógica de Retorno de Pago (Stripe Success)
        if (request()->has('payment') && request('payment') == 'success') {
            if (!$this->ticket->is_paid) {
                $this->ticket->update(['is_paid' => true, 'status' => 'open']);

                // Notificar a todos los expertos de esa categoría
                $matchingExperts = User::where('role', 'expert')
                    ->where('expertise', $this->ticket->category)
                    ->get();

                foreach ($matchingExperts as $expert) {
                    $expert->notify(new NewWorkOpportunity($this->ticket));
                }
            }
        }
    }

    public function sendMessage(WhatsAppService $whatsapp)
    {
        // 1. Validaciones
        $this->validate([
            'newMessage' => 'required_without:image',
            'image' => 'nullable|image|max:10240', // 10MB Máx
        ]);

        // 2. Guardar Imagen (si hay)
        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('chat-images', 'public');
        }

        // 3. Guardar en Base de Datos (La verdad absoluta)
        $message = Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => Auth::id(),
            'body' => $this->newMessage ?? '📷 Imagen adjunta',
            'attachment' => $imagePath,
        ]);

        // 4. Actualizar Web en Tiempo Real (Reverb)
        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            // Si falla Reverb, no pasa nada, el Polling lo resuelve
        }

        // --- 5. PUENTE A WHATSAPP (La Magia Omnicanal) ---
        // Solo si escribe el EXPERTO o ADMIN
        if (Auth::user()->role === 'expert' || Auth::user()->role === 'admin') {

            // Y si el cliente tiene número de teléfono registrado
            if ($this->ticket->user->phone) {

                $textoParaEnviar = $this->newMessage;

                // Si mandó imagen, enviamos el link público (WhatsApp API simple no soporta adjuntos directos fácil)
                if ($imagePath) {
                    $linkImagen = asset('storage/' . $imagePath);
                    $textoParaEnviar = $textoParaEnviar . "\n\n📷 *Ver imagen adjunta:* " . $linkImagen;
                }

                // Enviamos la copia al celular del cliente
                if (!empty($textoParaEnviar) || $imagePath) {
                    $whatsapp->send($this->ticket->user->phone, $textoParaEnviar ?? '📷 Imagen enviada');
                }
            }
        }
        // ------------------------------------------------

        // 6. Limpieza
        $this->reset(['newMessage', 'image']);
        $this->dispatch('message-sent');
    }

    // Configuración de WebSockets para recibir
    public function getListeners()
    {
        return ["echo:ticket.{$this->ticket->uuid},MessageSent" => '$refresh'];
    }

    // Método dummy para el Polling (wire:poll)
    public function loadMessages() {}

    // Generar link de pago nuevo
    public function payNow(StripeService $stripe)
    {
        $checkoutUrl = $stripe->createCheckoutSession($this->ticket);
        return redirect($checkoutUrl);
    }

    // Finalizar Ticket
    public function closeTicket(WhatsAppService $whatsapp)
    {
        if (Auth::id() !== $this->ticket->expert_id && Auth::user()->role !== 'admin') {
            return;
        }

        $this->ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Aviso en Chat Web
        Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => null,
            'body' => '🔒 El experto ha marcado este ticket como FINALIZADO.',
        ]);

        // Aviso por WhatsApp al cliente
        if ($this->ticket->user->phone) {
            $whatsapp->send($this->ticket->user->phone, "🏁 *Ticket Finalizado*\nEl experto ha cerrado tu caso. Entra a la web si deseas calificar el servicio.");
        }

        $this->dispatch('message-sent');
    }

    // Calificar
    public function rateService()
    {
        if (Auth::id() !== $this->ticket->user_id) return;

        $this->ticket->update([
            'rating' => $this->rating,
            'review' => $this->review
        ]);

        session()->flash('success', '¡Gracias por tu opinión!');
    }
    public function redactMessage($messageId)
    {
        // 1. Buscar el mensaje
        $msg = Message::find($messageId);

        // 2. Seguridad: Solo Admin o el Experto asignado pueden censurar
        if (!$msg || (Auth::user()->role !== 'admin' && Auth::id() !== $this->ticket->expert_id)) {
            return;
        }

        // 3. "Quemar" el contenido sensible
        $msg->update([
            'body' => '🔒 [DATOS SENSIBLES ELIMINADOS POR SEGURIDAD]',
            'attachment' => null // También borramos adjuntos si los hubiera
        ]);

        // 4. Actualizar la vista de todos
        try {
            // Reutilizamos el evento MessageSent para actualizar la UI
            // (Aunque técnicamente es un update, esto forzará el refresh)
            broadcast(new MessageSent($msg));
        } catch (\Exception $e) {
        }

        $this->dispatch('message-sent');
    }
    public function saveDispute()
    {
        // Validación
        $this->validate([
            'disputeReasonText' => 'required|min:10|max:255'
        ]);

        if (Auth::id() !== $this->ticket->user_id) return;

        // 1. Actualizar el Ticket con la bandera y el motivo
        $this->ticket->update([
            'is_disputed' => true,
            'dispute_reason' => $this->disputeReasonText
        ]);

        // 2. Agregar un mensaje automático en el chat (Para que quede en el historial)
        Message::create([
            'ticket_id' => $this->ticket->id,
            'user_id' => null, // Sistema
            'body' => "🚨 RECLAMO DEL CLIENTE: \n" . $this->disputeReasonText
        ]);

        $this->showDisputeForm = false;
        $this->dispatch('message-sent');
    }
    public function render()
    {
        return view('livewire.chat.chat-room', [
            'messages' => $this->ticket->messages()->with('user')->get()
        ])->layout('layouts.app');
    }
}
