<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Message;
use App\Services\WhatsAppService;
use App\Notifications\TicketDisputeResolvedNotification;
use App\Events\MessageSent;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination; // Importante para la tabla

    public $showResolutionModal = false;
    public $selectedTicketId = null;
    public $resolutionText = '';

    // 1. ABRIR EL MODAL (Reemplaza a la vieja resolveDispute)
    public function openResolutionModal($ticketId)
    {
        $this->selectedTicketId = $ticketId;
        $this->resolutionText = ''; // Limpiar texto anterior
        $this->showResolutionModal = true;
    }

    // 2. GUARDAR Y RESOLVER (Acción final)
    public function saveResolution()
    {
        $this->validate(['resolutionText' => 'required|min:5']);

        $ticket = Ticket::find($this->selectedTicketId);

        if ($ticket) {
            // A. Quitar alerta en BD
            $ticket->update(['is_disputed' => false]);

            // B. Mensaje en el Chat (Con la explicación del Admin)
            $msgBody = "👮‍♂️ RESOLUCIÓN DEL ADMINISTRADOR:\n" . $this->resolutionText;

            $msg = Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => null, // null = Sistema
                'body' => $msgBody
            ]);

            // Avisar en tiempo real al chat
            try {
                broadcast(new MessageSent($msg));
            } catch (\Exception $e) {
            }

            // C. Notificación Email (Pasamos el texto de resolución)
            $ticket->user->notify(new TicketDisputeResolvedNotification($ticket, $this->resolutionText));

            // D. WhatsApp
            if ($ticket->user->phone) {
                $whatsapp = app(WhatsAppService::class);

                $waMsg = "👮‍♂️ *Reporte Atendido* \n\n" .
                    "Hola *{$ticket->user->name}*, un administrador revisó tu caso.\n\n" .
                    "📝 *Resolución:* {$this->resolutionText}\n\n" .
                    "✅ La disputa ha sido marcada como resuelta.\n" .
                    "Puedes continuar en el chat: \n" . route('ticket.chat', $ticket->uuid);

                $whatsapp->send($ticket->user->phone, $waMsg);
            }

            session()->flash('message', 'Resolución enviada correctamente.');
        }

        // Cerrar Modal y Limpiar
        $this->showResolutionModal = false;
        $this->selectedTicketId = null;
    }

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

            // Datos para Gráficas
            'chartIncomeLabels' => $incomeData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M')),
            'chartIncomeValues' => $incomeData->pluck('total'),
            'chartCatLabels' => $categoryData->pluck('category'),
            'chartCatValues' => $categoryData->pluck('total'),

            // Tabla
            'tickets' => Ticket::with(['user', 'expert'])
                ->latest()
                ->paginate(10)
        ]);
    }
}
