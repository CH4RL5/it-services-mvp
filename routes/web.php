<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Chat\ChatRoom;
use App\Livewire\Expert\TicketList;
use App\Livewire\Welcome;

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

require __DIR__.'/auth.php';
