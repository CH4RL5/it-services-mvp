<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Chat\ChatRoom;
use App\Livewire\Expert\TicketList;
use App\Livewire\Welcome;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

    // --- ESTA ES LA RUTA QUE TE FALTABA (Notificaciones) ---
    Route::post('/notifications/mark-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->noContent();
    })->name('notifications.markRead');
    // -------------------------------------------------------
});
// Ruta de Login Automático (Inseguro para prod, perfecto para Hackathon)
Route::get('/magic-login/{phone}', function ($phone) {
    $user = User::where('phone', $phone)->firstOrFail();
    Auth::login($user);
    return redirect()->route('dashboard'); // Lo manda directo al panel
})->name('magic.login');

require __DIR__.'/auth.php';
