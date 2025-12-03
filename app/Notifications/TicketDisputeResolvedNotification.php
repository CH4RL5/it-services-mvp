<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketDisputeResolvedNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $resolutionMessage; // <--- NUEVA VARIABLE

    public function __construct($ticket, $resolutionMessage) // <--- RECIBIR AQUÍ
    {
        $this->ticket = $ticket;
        $this->resolutionMessage = $resolutionMessage;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Resolución de Disputa - Ticket #' . substr($this->ticket->uuid, 0, 6))
            ->greeting('Hola ' . $notifiable->name)
            ->line('El administrador ha dictado una resolución sobre tu caso:')

            // MOSTRAR EL MENSAJE DEL ADMIN
            ->line('📝 "' . $this->resolutionMessage . '"')

            ->line('El estado de disputa ha sido levantado.')
            ->action('Ver Ticket', route('ticket.chat', $this->ticket->uuid));
    }
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_uuid' => $this->ticket->uuid,
            'message' => 'Tu reclamo ha sido atendido por un administrador.',
        ];
    }
}
