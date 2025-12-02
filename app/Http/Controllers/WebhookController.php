<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Message;
use App\Services\GeminiService;
use App\Services\StripeService;
use Illuminate\Support\Str;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === 'mimic_mvp_secret_token') {
            return response($challenge, 200);
        }
        return response('Forbidden', 403);
    }

    public function handleIncomingMessage(Request $request, GeminiService $ai, StripeService $stripe)
    {
        try {
            $body = $request->all();

            // Validación de estructura de mensaje
            if (!isset($body['entry'][0]['changes'][0]['value']['messages'][0])) {
                return response()->json(['status' => 'ignored']);
            }

            $msgData = $body['entry'][0]['changes'][0]['value']['messages'][0];
            $phone = $msgData['from'];
            $text = $msgData['text']['body'] ?? '';

            if (empty($text)) return response()->json(['status' => 'empty']);

            // 1. BUSCAR USUARIO
            $user = User::where('phone', $phone)->first();

            // --- CASO 0: USUARIO NUEVO (REGISTRO) ---
            if (!$user) {
                // Creamos el usuario temporal
                $user = User::create([
                    'name' => 'Invitado',
                    'email' => $phone . '@whatsapp.com',
                    'password' => bcrypt('password'),
                    'phone' => $phone,
                    'role' => 'client',
                    'conversation_step' => 'WAITING_NAME' // Estado 1: Esperando Nombre
                ]);

                // Enviamos la pregunta y PARAMOS AQUÍ (return)
                $this->sendWhatsApp($phone, "👋 ¡Hola! Bienvenido a Mimic IT.\n\nPara poder atenderte, dime: \n\n*¿Cuál es tu nombre?*");
                return response()->json(['status' => 'asked_name']);
            }

            // --- CASO 1: ESPERANDO NOMBRE ---
            if ($user->conversation_step === 'WAITING_NAME') {
                $user->update([
                    'name' => $text, // Guardamos lo que escribió ahora (ej: "Juan")
                    'conversation_step' => 'WAITING_PROBLEM' // Estado 2: Esperando Problema
                ]);

                // Preguntamos el problema y PARAMOS AQUÍ
                $this->sendWhatsApp($phone, "¡Un gusto, {$text}! 🤝\n\nAhora cuéntame brevemente:\n*¿Qué problema técnico tienes?*");
                return response()->json(['status' => 'saved_name']);
            }

            // --- CASO 2: ESPERANDO PROBLEMA (CREAR TICKET) ---
            // Verificamos si NO tiene tickets activos para no duplicar
            $activeTicket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'pending_payment'])
                ->exists();

            if (!$activeTicket && ($user->conversation_step === 'WAITING_PROBLEM' || $user->conversation_step === null)) {

                // A. Crear Ticket
                $category = $ai->classifyTicket($text); // Usamos IA

                $ticket = Ticket::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'title' => Str::limit($text, 40),
                    'description' => $text,
                    'category' => $category,
                    'status' => 'pending_payment'
                ]);

                // B. Generar Link de Pago (Con Fallback por si Stripe falla)
                $paymentLink = "";
                try {
                    $paymentLink = $stripe->createCheckoutSession($ticket);
                } catch (\Exception $e) {
                    Log::error("Error Stripe: " . $e->getMessage());
                    // Si falla Stripe, mandamos el link directo al chat para no perder al cliente
                    $paymentLink = route('ticket.chat', $ticket->uuid);
                }

                // C. Link de Acceso Mágico
                $magicLink = route('magic.login', ['phone' => $phone]);

                // D. Enviar Respuesta Final
                $mensaje = "🤖 *Ticket Generado* \n" .
                    "👤 Cliente: *{$user->name}* \n" .
                    "📂 Categoría: *{$category}* \n\n" .
                    "💳 *PASO 1: Activa el servicio:* \n{$paymentLink} \n\n" .
                    "🚀 *Entra al Panel Web:* \n{$magicLink}";

                $this->sendWhatsApp($phone, $mensaje);

                // Reiniciar estado
                $user->update(['conversation_step' => null]);
                return response()->json(['status' => 'ticket_created']);
            }

            // --- CASO 3: CHAT ACTIVO (Ya tiene ticket) ---
            // Si llega aquí, es porque ya tiene ticket y solo quiere chatear
            $latestTicket = Ticket::where('user_id', $user->id)->latest()->first();

            if ($latestTicket) {
                $message = Message::create([
                    'ticket_id' => $latestTicket->id,
                    'user_id' => $user->id,
                    'body' => $text,
                    'source' => 'whatsapp'
                ]);

                try {
                    broadcast(new MessageSent($message));
                } catch (\Exception $e) {
                }
            }

            return response()->json(['status' => 'processed']);
        } catch (\Exception $e) {
            Log::error("🔥 ERROR CRÍTICO: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function sendWhatsApp($to, $text)
    {
        // Parche México
        if (str_starts_with($to, '521')) {
            $to = '52' . substr($to, 3);
        }

        $token = env('META_WHATSAPP_TOKEN');
        $phoneId = env('META_PHONE_ID');
        $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

        \Illuminate\Support\Facades\Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text]
        ]);
    }
}
