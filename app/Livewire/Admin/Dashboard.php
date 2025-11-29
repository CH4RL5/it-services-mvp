<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\User;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            // Métricas KPI
            'totalRevenue' => Ticket::where('is_paid', true)->sum('amount'),
            'totalTickets' => Ticket::count(),
            'activeExperts' => User::where('role', 'expert')->count(),

            // Lista completa para auditar
            'tickets' => Ticket::with(['user', 'expert'])
                ->latest()
                ->paginate(10)
        ]);
    }
}
