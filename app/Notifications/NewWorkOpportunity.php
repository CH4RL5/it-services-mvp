<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewWorkOpportunity extends Notification
{
    use Queueable;
    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Email y Campanita
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('💰 Nuevo Ticket de ' . $this->ticket->category)
            ->line('Hay un nuevo trabajo disponible que coincide con tu especialidad.')
            ->line('Problema: ' . substr($this->ticket->title, 0, 50) . '...')
            ->action('Tomar Ticket', route('expert.dashboard'))
            ->line('¡Corre antes de que otro experto lo tome!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_uuid' => $this->ticket->uuid,
            'message' => 'Nuevo ticket disponible: ' . $this->ticket->category,
        ];
    }
}
