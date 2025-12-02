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

            // --- CASO A: USUARIO NUEVO (REGISTRO AUTOMÁTICO) ---
            if (!$user) {
                $password = \Illuminate\Support\Str::random(10); // Generar pass real

                $user = User::create([
                    'name' => 'Amigo', // Nombre temporal
                    'email' => $phone . '@whatsapp.com',
                    'password' => bcrypt($password),
                    'phone' => $phone,
                    'role' => 'client',
                    'conversation_step' => 'WAITING_NAME' // <--- Paso 1: Pedir Nombre
                ]);

                $this->sendWhatsApp($phone, "👋 ¡Hola! Bienvenido a Mimic IT.\n\nVeo que es tu primera vez aquí. Para poder atenderte mejor, dime: \n\n*¿Cuál es tu nombre?*");
                return response()->json(['status' => 'asked_name']);
            }

            // --- CASO B: FLUJO DE CONVERSACIÓN ---

            // 1. Si estamos esperando el NOMBRE (Viene del Caso A)
            if ($user->conversation_step === 'WAITING_NAME') {
                $user->update([
                    'name' => $text,
                    'conversation_step' => 'WAITING_PROBLEM' // <--- Paso 2: Pedir Problema
                ]);

                $this->sendWhatsApp($phone, "¡Mucho gusto, {$text}! 🤝\n\nTu cuenta ha sido creada. Ahora cuéntame: \n*¿Qué problema técnico necesitas resolver hoy?*");
                return response()->json(['status' => 'saved_name']);
            }

            // 2. Si es USUARIO RECURRENTE (Ya existía y manda "Hola")
            $activeTicket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'pending_payment'])
                ->exists();

            // Si NO tiene ticket y NO estamos esperando problema, lo saludamos primero
            if (!$activeTicket && $user->conversation_step === null) {
                // Lo ponemos en modo "Esperando Problema" para el siguiente mensaje
                $user->update(['conversation_step' => 'WAITING_PROBLEM']);

                $this->sendWhatsApp($phone, "👋 ¡Hola de nuevo, {$user->name}!\n\n¿En qué podemos ayudarte hoy? Describe tu problema en el siguiente mensaje.");
                return response()->json(['status' => 'welcome_back']);
            }

            // 3. CREAR TICKET (Si ya estamos esperando el problema)
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

                // Generar Link Pago
                try {
                    $paymentLink = $stripe->createCheckoutSession($ticket);
                } catch (\Exception $e) {
                    $paymentLink = route('ticket.chat', $ticket->uuid);
                }

                // Generar Link Mágico
                $magicLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'magic.login',
                    now()->addHour(),
                    ['user' => $user->id]
                );

                // MENSAJE FINAL (Diferente si es nuevo o viejo)
                // Usamos "wasRecentlyCreated" o verificamos la fecha de creación
                $esNuevo = $user->created_at->diffInMinutes(now()) < 5;

                $mensaje = "🤖 *Ticket Generado* \n" .
                    "📂 Categoría: *{$category}* \n\n" .
                    "💳 *PASO 1: Paga aquí:* \n{$paymentLink} \n\n";

                if ($esNuevo) {
                    // Si se acaba de registrar, le damos la bienvenida oficial
                    $mensaje .= "🔐 *PASO 2: Tu Cuenta:* \n" .
                        "Ya estás registrado con tu número. \n\n";
                } else {
                    $mensaje .= "🔐 *PASO 2: Acceso:* \n" .
                        "Usa tu cuenta habitual. \n\n";
                }

                $mensaje .= "🚀 *Entra directo sin clave:* \n{$magicLink}";

                $this->sendWhatsApp($phone, $mensaje);

                // Resetear paso
                $user->update(['conversation_step' => null]);

                return response()->json(['status' => 'ticket_created']);
            }

            // CASO C: TIENE TICKET ACTIVO (Chat Normal)
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
