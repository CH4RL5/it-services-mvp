<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Chat\ChatRoom;
use App\Livewire\Expert\TicketList;
use App\Livewire\Welcome;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket;
use App\Services\WhatsAppService; // Importante
use App\Notifications\NewWorkOpportunity;
// 1. Landing Page (Página de Inicio)
Route::view('/', 'welcome')->name('home');

// 2. Rutas Protegidas (Requieren Login)
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Principal (Cliente/Admin)
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Perfil de Usuario
    Route::view('profile', 'profile')->name('profile');

    // Sala de Chat
    Route::get('/ticket/{ticket}', ChatRoom::class)->name('ticket.chat');

    // Panel del Experto (Dashboard de trabajo)
    Route::get('/expert/dashboard', TicketList::class)->name('expert.dashboard');

    // --- ESTA ES LA RUTA DE (Notificaciones) ---
    Route::post('/notifications/mark-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->noContent();
    })->name('notifications.markRead');

    // Ruta para Gestión de Expertos (Solo Admin)
    Route::get('/admin/experts', function () {
        // Doble seguridad: Si no es admin, fuera.
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }
        return view('livewire.admin.experts'); // Busca en resources/views/livewire/admin/
    })->name('admin.experts');
    // -------------------------------------------------------
});
// Ruta de Login Automático (Inseguro para prod, perfecto para Hackathon)
Route::get('/magic-login/{phone}', function ($phone) {
    $user = User::where('phone', $phone)->firstOrFail();
    Auth::login($user);
    return redirect()->route('dashboard'); // Lo manda directo al panel
})->name('magic.login');
// RUTA DE RETORNO DE STRIPE (Pública pero segura por UUID)
Route::get('/payment/success/{ticket}', function (Ticket $ticket, WhatsAppService $whatsapp) {

    // 1. Validar y Actualizar Pago
    if (!$ticket->is_paid) {
        $ticket->update([
            'is_paid' => true,
            'status' => 'open'
        ]);

        // 2. NOTIFICACIÓN POR WHATSAPP (Confirmación Inmediata) 📲
        if ($ticket->user->phone) {
            $msg = "✅ *¡Pago Recibido con Éxito!*\n\n" .
                "Tu ticket de *{$ticket->category}* está activo.\n" .
                "Estamos buscando un experto para ti. Te notificaremos por aquí en cuanto te asignen.";

            $whatsapp->send($ticket->user->phone, $msg);
        }

        // 3. MATCHMAKING (Avisar a Expertos)
        $matchingExperts = User::where('role', 'expert')
            ->where('expertise', $ticket->category)
            ->get();

        foreach ($matchingExperts as $expert) {
            $expert->notify(new NewWorkOpportunity($ticket));
        }
    }

    // 4. AUTO-LOGIN (Magia ✨)
    // Si el usuario no está logueado, lo logueamos forzosamente usando el dueño del ticket
    if (!Auth::check()) {
        Auth::login($ticket->user);
    }

    // 5. REDIRIGIR AL CHAT WEB
    return redirect()->route('ticket.chat', $ticket->uuid);
})->name('payment.callback');
require __DIR__.'/auth.php';
