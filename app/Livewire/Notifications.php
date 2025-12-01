<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Notifications extends Component
{
    public function markAsRead()
    {
        // Marca todas como leídas en la BD
        Auth::user()->unreadNotifications->markAsRead();

        // Livewire se renderiza de nuevo automáticamente y la lista aparecerá vacía
    }

    public function render()
    {
        return view('livewire.notifications', [
            'notifications' => Auth::user()->unreadNotifications
        ]);
    }
}
