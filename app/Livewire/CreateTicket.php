<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Services\GeminiService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends Component
{
    public $description = '';

    public function save(GeminiService $gemini)
    {
      
        $this->validate([
            'description' => 'required|min:10|max:500',
        ]);


        $category = $gemini->classifyTicket($this->description);


        $ticket = Ticket::create([
            'uuid' => Str::uuid(),
            'user_id' => Auth::id(), // El usuario logueado
            'title' => Str::limit($this->description, 30), // Título corto automático
            'description' => $this->description,
            'category' => $category, // ¡Aquí entra la IA!
            'status' => 'pending_payment', // Primero paga, luego chat
        ]);

        // 4. Feedback visual (Mañana haremos la redirección al pago)
        session()->flash('message', "¡Ticket creado! Categoría detectada: " . $category);
        $this->reset('description');
    }

    public function render()
    {
        return view('livewire.create-ticket');
    }
}
