<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        // Enviamos por Email y guardamos en Base de Datos (Campanita)
        return ['mail', 'database'];
    }

    // Diseño del Correo Electrónico
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚀 ¡Experto en camino!')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Buenas noticias: Un experto ha tomado tu ticket de ' . $this->ticket->category . '.')
            ->line('Ya puedes entrar al chat para resolver tu problema.')
            ->action('Ir al Chat', route('ticket.chat', $this->ticket->uuid))
            ->line('Gracias por confiar en Mimic MVP.');
    }

    // Diseño de la Alerta en el Panel (Campanita)
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_uuid' => $this->ticket->uuid,
            'message' => 'Tu ticket #' . substr($this->ticket->uuid, 0, 8) . ' ha sido aceptado por un experto.',
            'category' => $this->ticket->category,
        ];
    }
}
