<?php

namespace App\Livewire\Expert;

use Livewire\Component;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use App\Notifications\TicketAssignedNotification;

class TicketList extends Component
{
    // Variables para el Modal
    public $selectedTicket = null;
    public $showModal = false;

    // 1. Acción: Ver detalles (Abre el modal)
    public function viewDetails($uuid)
    {
        $this->selectedTicket = Ticket::where('uuid', $uuid)->first();
        $this->showModal = true;
    }

    // 2. Acción: Rechazar (Cierra el modal y limpia selección)
    public function rejectTicket()
    {
        $this->reset(['showModal', 'selectedTicket']);
    }

    // 3. Acción: Aceptar (Lo toma y notifica)
    public function takeTicket()
    {
        if (!$this->selectedTicket) return;

        // Validar que siga libre (por si otro experto ganó el click)
        if ($this->selectedTicket->expert_id) {
            session()->flash('error', '¡Ups! Otro experto acaba de tomar este ticket.');
            $this->rejectTicket();
            return;
        }

        $this->selectedTicket->update([
            'expert_id' => Auth::id(),
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Notificar al cliente
        $this->selectedTicket->user->notify(new TicketAssignedNotification($this->selectedTicket));

        return redirect()->route('ticket.chat', $this->selectedTicket->uuid);
    }

    public function render()
    {
        $user = Auth::user();

        // Query Base: Tickets pagados y sin asignar
        $query = Ticket::where('is_paid', true)
            ->whereNull('expert_id');

        // MATCHMAKING: Si el experto tiene una especialidad, filtramos por ella.
        // Si su especialidad es null, asume que es un "Super Experto" y ve todo.
        if ($user->expertise) {
            // Buscamos coincidencia exacta o parcial (usando LIKE por si la IA varía un poco)
            $query->where('category', 'LIKE', '%' . $user->expertise . '%');
        }

        return view('livewire.expert.ticket-list', [
            'availableTickets' => $query->latest()->get(),

            'myTickets' => Ticket::where('expert_id', Auth::id())
                ->latest()
                ->get()
        ]);
    }
}
