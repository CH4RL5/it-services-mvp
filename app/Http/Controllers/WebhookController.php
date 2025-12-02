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
            if (!isset($body['entry'][0]['changes'][0]['value']['messages'][0])) return response()->json(['status' => 'ignored']);

            $msgData = $body['entry'][0]['changes'][0]['value']['messages'][0];
            $phone = $msgData['from'];
            $text = $msgData['text']['body'] ?? '';

            if (empty($text)) return response()->json(['status' => 'empty']);

            // 1. BUSCAR USUARIO
            $user = User::where('phone', $phone)->first();

            // --- CASO 0: USUARIO NUEVO (REGISTRO INICIAL) ---
            if (!$user) {
                // Creamos el usuario con datos temporales
                $user = User::create([
                    'name' => 'Invitado',
                    'email' => $phone . '@whatsapp.com', // Temporal
                    'password' => bcrypt(Str::random(16)),
                    'phone' => $phone,
                    'role' => 'client',
                    'conversation_step' => 'WAITING_NAME' // PASO 1: Pedir Nombre
                ]);

                $this->sendWhatsApp($phone, "👋 ¡Hola! Bienvenido a Mimic IT.\n\nPara poder atenderte, por favor dime: \n\n*¿Cuál es tu nombre?*");
                return response()->json(['status' => 'asked_name']);
            }

            // --- CASO 1: FLUJO DE REGISTRO (MÁQUINA DE ESTADOS) ---

            // A) ESPERANDO NOMBRE ➡ PEDIR EMAIL
            if ($user->conversation_step === 'WAITING_NAME') {
                $user->update([
                    'name' => $text,
                    'conversation_step' => 'WAITING_EMAIL' // <--- NUEVO ESTADO
                ]);

                $this->sendWhatsApp($phone, "¡Un gusto, {$text}! 🤝\n\nPara crear tu cuenta, necesito tu *Correo Electrónico*:");
                return response()->json(['status' => 'asked_email']);
            }

            // B) ESPERANDO EMAIL ➡ PEDIR PROBLEMA
            if ($user->conversation_step === 'WAITING_EMAIL') {

                // Validamos que parezca un correo
                if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $this->sendWhatsApp($phone, "⚠️ Eso no parece un correo válido. Intenta de nuevo:");
                    return response()->json(['status' => 'invalid_email']);
                }

                // Intentamos guardar el correo (si no está duplicado)
                try {
                    $user->update([
                        'email' => $text,
                        'email_verified_at' => now(), // ✅ Lo verificamos porque nos lo dio por WhatsApp (Canal seguro)
                        'conversation_step' => 'WAITING_PROBLEM'
                    ]);
                } catch (\Exception $e) {
                    $this->sendWhatsApp($phone, "⚠️ Ese correo ya está registrado en nuestro sistema. Por favor escribe otro:");
                    return response()->json(['status' => 'duplicate_email']);
                }

                $this->sendWhatsApp($phone, "✅ ¡Cuenta configurada!\n\nAhora sí, cuéntame: *¿Qué problema técnico tienes hoy?*");
                return response()->json(['status' => 'saved_email']);
            }

            // --- CASO 2: LOGICA DE TICKET ---

            // Verificamos si ya tiene ticket abierto
            $activeTicket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'pending_payment'])
                ->exists();

            // SI ES USUARIO RECURRENTE (Ya no tiene pasos pendientes)
            if (!$activeTicket && $user->conversation_step === null) {
                // Lo saludamos y lo ponemos a esperar el problema
                $user->update(['conversation_step' => 'WAITING_PROBLEM']);

                $this->sendWhatsApp($phone, "👋 ¡Hola de nuevo, {$user->name}!\n\n¿En qué podemos ayudarte hoy? Describe tu problema:");
                return response()->json(['status' => 'welcome_back']);
            }

            // SI ESTAMOS ESPERANDO EL PROBLEMA ➡ CREAR TICKET
            if (!$activeTicket && $user->conversation_step === 'WAITING_PROBLEM') {

                $category = $ai->classifyTicket($text);

                $ticket = Ticket::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'title' => Str::limit($text, 30),
                    'description' => $text,
                    'category' => $category,
                    'status' => 'pending_payment'
                ]);

                // Links
                try {
                    $paymentLink = $stripe->createCheckoutSession($ticket);
                } catch (\Exception $e) {
                    $paymentLink = route('ticket.chat', $ticket->uuid);
                }

                $magicLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'magic.login',
                    now()->addHour(),
                    ['user' => $user->id]
                );

                $mensaje = "🤖 *Ticket Generado* \n" .
                    "👤 Cliente: *{$user->name}* \n" .
                    "📂 Categoría: *{$category}* \n\n" .
                    "💳 *Paga aquí:* \n{$paymentLink} \n\n" .
                    "🚀 *Entra a tu Panel:* \n{$magicLink} \n\n" .
                    "ℹ️ _Tu usuario es tu correo: {$user->email}_";

                $this->sendWhatsApp($phone, $mensaje);

                // Finalizamos el flujo
                $user->update(['conversation_step' => null]);

                return response()->json(['status' => 'ticket_created']);
            }

            // --- CASO 3: TICKET ACTIVO (CHAT) ---
            // Si ya tiene ticket, el mensaje va al chat, NO al bot
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
            Log::error("🔥 ERROR: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
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
