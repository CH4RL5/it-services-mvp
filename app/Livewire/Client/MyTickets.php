<?php

namespace App\Livewire\Client;

use Livewire\Component;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class MyTickets extends Component
{
    // Escuchar eventos: Si crea un ticket nuevo (en el otro componente), esta lista se actualiza sola
    protected $listeners = ['ticketCreated' => '$refresh'];

    public function render()
    {
        return view('livewire.client.my-tickets', [
            'tickets' => Ticket::where('user_id', Auth::id())
                ->latest()
                ->get()
        ]);
    }
}
