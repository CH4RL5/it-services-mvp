<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Importante: Now
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// "ShouldBroadcastNow" significa que se envía al instante, sin pasar por colas de espera
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $ticketUuid;

    public function __construct(Message $message)
    {
        $this->message = $message;
        // Cargamos la relación usuario para enviar su nombre y avatar al instante
        $this->message->load('user');
        $this->ticketUuid = $message->ticket->uuid;
    }

    public function broadcastOn(): array
    {
        // Creamos un canal privado único para este ticket
        return [
            new Channel('ticket.' . $this->ticketUuid),
        ];
    }
}
