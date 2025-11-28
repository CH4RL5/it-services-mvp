<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Rutas para WhatsApp
Route::get('/whatsapp/webhook', [WebhookController::class, 'verifyWebhook']); // Para verificar
Route::post('/whatsapp/webhook', [WebhookController::class, 'handleIncomingMessage']); // Para recibir
