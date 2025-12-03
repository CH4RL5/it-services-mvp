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

            // COMANDO DE RESET (Por si se traba)
            if (strtolower($text) === 'cancelar' || strtolower($text) === 'reiniciar') {
                if ($user) $user->update(['conversation_step' => null]);
                $this->sendWhatsApp($phone, "🔄 Operación cancelada. Escribe 'Hola' para empezar de nuevo.");
                return response()->json(['status' => 'reset']);
            }

            // --- CASO 0: USUARIO NUEVO ---
            if (!$user) {
                $user = User::create([
                    'name' => 'Invitado',
                    'email' => $phone . '@whatsapp.com',
                    'password' => bcrypt(Str::random(16)),
                    'phone' => $phone,
                    'role' => 'client',
                    'email_verified_at' => now(), // CORRECCIÓN 1: Usuario verificado desde nacer
                    'conversation_step' => 'WAITING_NAME'
                ]);

                $this->sendWhatsApp($phone, "👋 ¡Hola! Bienvenido a Mimic IT.\n\nPara atenderte, dime: \n\n*¿Cuál es tu nombre?*");
                return response()->json(['status' => 'asked_name']);
            }

            // --- CASO 1: MÁQUINA DE ESTADOS ---

            // A) ESPERANDO NOMBRE
            if ($user->conversation_step === 'WAITING_NAME') {
                $user->update([
                    'name' => $text,
                    'conversation_step' => 'WAITING_EMAIL'
                ]);
                $this->sendWhatsApp($phone, "¡Un gusto, {$text}! 🤝\n\nAhora escribe tu *Correo Electrónico* para crear tu cuenta:");
                return response()->json(['status' => 'asked_email']);
            }

            // B) ESPERANDO EMAIL (CORRECCIÓN 1 Y 2)
            if ($user->conversation_step === 'WAITING_EMAIL') {

                // Validación básica de email
                if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $this->sendWhatsApp($phone, "⚠️ Eso no parece un correo. Intenta de nuevo o escribe 'Cancelar':");
                    return response()->json(['status' => 'invalid_email']);
                }

                try {
                    $user->update([
                        'email' => $text,
                        'email_verified_at' => now(), // <--- ¡AQUÍ ESTABA EL ERROR! Ahora sí se verifica.
                        'conversation_step' => 'WAITING_PROBLEM'
                    ]);
                } catch (\Exception $e) {
                    $this->sendWhatsApp($phone, "⚠️ Ese correo ya existe. Usa otro o escribe 'Cancelar'.");
                    return response()->json(['status' => 'duplicate_email']);
                }

                $this->sendWhatsApp($phone, "✅ Cuenta configurada.\n\nCuéntame: *¿Qué problema técnico tienes hoy?*");
                return response()->json(['status' => 'saved_email']);
            }

            // --- CASO 2: TICKET ---

            // Verificar si tiene ticket abierto
            $activeTicket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'pending_payment'])
                ->exists();

            // Si es usuario recurrente (sin tickets, sin pasos pendientes)
            if (!$activeTicket && $user->conversation_step === null) {
                $user->update(['conversation_step' => 'WAITING_PROBLEM']);
                $this->sendWhatsApp($phone, "👋 ¡Hola de nuevo, {$user->name}!\n\nDescribe tu problema para crear un nuevo ticket:");
                return response()->json(['status' => 'welcome_back']);
            }

            // CREAR TICKET (Si estamos esperando el problema)
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

                // CORRECCIÓN 2: STRIPE BLINDADO
                $paymentLink = "";
                try {
                    $paymentLink = $stripe->createCheckoutSession($ticket);
                } catch (\Exception $e) {
                    Log::error("Stripe Error: " . $e->getMessage());
                    // Si falla, mandamos link al chat normal
                    $paymentLink = route('ticket.chat', $ticket->uuid);
                }

                $magicLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'magic.login',
                    now()->addHour(),
                    ['user' => $user->id]
                );

                $mensaje = "🤖 *Ticket Generado* \n" .
                    "📂 Categoría: *{$category}* \n\n" .
                    "💳 *Paga aquí:* \n{$paymentLink} \n\n" .
                    "🚀 *Panel Web:* \n{$magicLink} \n\n" .
                    "ℹ️ _Usuario: {$user->email}_";

                $this->sendWhatsApp($phone, $mensaje);

                // CORRECCIÓN 3: LIMPIAR ESTADO PARA QUE NO SE TRABE
                $user->update(['conversation_step' => null]);

                return response()->json(['status' => 'ticket_created']);
            }

            // --- CASO 3: CHAT ACTIVO ---
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
