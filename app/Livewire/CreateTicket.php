<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Services\GeminiService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\StripeService;
class CreateTicket extends Component
{
    public $description = '';

    public function save(GeminiService $gemini, StripeService $stripe)
    {
        $this->validate([
            'description' => 'required|min:10|max:500',
        ]);

        // 1. Clasificar
        $category = $gemini->classifyTicket($this->description);

        // 2. Crear Ticket (Pendiente de pago)
        $ticket = Ticket::create([
            'uuid' => Str::uuid(),
            'user_id' => Auth::id(),
            'title' => Str::limit($this->description, 30),
            'description' => $this->description,
            'category' => $category,
            'status' => 'pending_payment',
        ]);
        $this->dispatch('ticketCreated');
        // 3. Generar Link de Pago y Redirigir
        $checkoutUrl = $stripe->createCheckoutSession($ticket);

        return redirect($checkoutUrl);
    }

    public function render()
    {
        return view('livewire.create-ticket');
    }
}
