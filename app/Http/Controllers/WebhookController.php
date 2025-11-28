<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\WhatsAppService;

class WebhookController extends Controller
{
    // 1. Verificación del Webhook (Lo que pide Facebook la primera vez)
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === env('META_VERIFY_TOKEN')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // 2. Recibir Mensajes Reales
    public function handleIncomingMessage(Request $request)
    {
        // Loguear para depurar
        Log::info('WhatsApp Webhook recibido:', $request->all());

        $body = $request->all();

        // Verificar si es un mensaje real
        if (isset($body['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $body['entry'][0]['changes'][0]['value']['messages'][0];
            $phone = $messageData['from']; // El número del cliente
            $text = $messageData['text']['body'] ?? ''; // El mensaje

            // AQUÍ VA LA MAGIA (Lo conectaremos mañana con Tickets)
            // Por ahora, solo respondemos automáticamente

            $waService = new WhatsAppService();
            $waService->sendMessage($phone, "🤖 Recibí tu mensaje: '$text'. (Sistema en construcción)");
        }

        return response()->json(['status' => 'received']);
    }
}
