<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\User;

class Dashboard extends Component
{
    public function render()
    {
        // 1. Datos para Gráfica de Ingresos (Últimos 7 días)
        $incomeData = Ticket::where('is_paid', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 2. Datos para Gráfica de Categorías (Pastel)
        $categoryData = Ticket::selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->get();

        return view('livewire.admin.dashboard', [
            // KPIs Generales
            'totalRevenue' => Ticket::where('is_paid', true)->sum('amount'),
            'totalTickets' => Ticket::count(),
            'activeExperts' => User::where('role', 'expert')->count(),

            // Datos para Gráficas (Arrays para JS)
            'chartIncomeLabels' => $incomeData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M')),
            'chartIncomeValues' => $incomeData->pluck('total'),
            'chartCatLabels' => $categoryData->pluck('category'),
            'chartCatValues' => $categoryData->pluck('total'),

            // Tabla (Igual que antes)
            'tickets' => Ticket::with(['user', 'expert'])
                ->latest()
                ->paginate(10)
        ]);
    }
    public function resolveDispute($ticketId)
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket) {
            // 1. Quitar la alerta roja
            $ticket->update(['is_disputed' => false]);

            // 2. Dejar constancia en el Chat (Mensaje del Sistema)
            \App\Models\Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => null, // null significa "Sistema" o "Bot"
                'body' => '👮‍♂️ El administrador ha revisado el caso y la disputa ha sido resuelta.'
            ]);
        }
    }
}
