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
use Illuminate\Support\Facades\Http; // Importante para la petición HTTP

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

            // Validación básica de estructura
            if (!isset($body['entry'][0]['changes'][0]['value']['messages'][0])) {
                return response()->json(['status' => 'ignored']);
            }

            $msgData = $body['entry'][0]['changes'][0]['value']['messages'][0];
            $phone = $msgData['from'];
            $text = $msgData['text']['body'] ?? '';

            if (empty($text)) return response()->json(['status' => 'empty']);

            Log::info("1. Mensaje recibido de: $phone");

            // 1. USUARIO
            $defaultPass = 'password';
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => 'Usuario WhatsApp',
                    'email' => $phone . '@whatsapp.com',
                    'password' => bcrypt($defaultPass),
                    'role' => 'client'
                ]
            );

            // 2. BUSCAR TICKET
            $ticket = Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'assigned', 'pending_payment'])
                ->latest()
                ->first();

            // 3. LÓGICA
            if (!$ticket) {
                Log::info("2. Creando nuevo ticket para: $phone");

                // --- NUEVO TICKET ---
                $category = $ai->classifyTicket($text);

                $ticket = Ticket::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'title' => Str::limit($text, 30),
                    'description' => $text,
                    'category' => $category,
                    'status' => 'pending_payment'
                ]);

                // --- STRIPE ---
                $paymentLink = "https://tu-sitio.com/login"; // Fallback por seguridad
                try {
                    $paymentLink = $stripe->createCheckoutSession($ticket);
                    Log::info("3. Stripe Link Generado: $paymentLink");
                } catch (\Exception $e) {
                    Log::error("❌ Error Stripe: " . $e->getMessage());
                    // Si falla Stripe, usamos el link al chat directo
                    $paymentLink = route('ticket.chat', $ticket->uuid);
                }

                // --- MENSAJE ---
                // Usamos try-catch aquí por si la ruta 'magic.login' no existe
                $magicLink = "";
                try {
                    $magicLink = route('magic.login', ['phone' => $phone]);
                } catch (\Exception $e) {
                    $magicLink = route('login'); // Fallback si no existe la ruta mágica
                }

                $mensaje = "🤖 *Ticket Generado* \n" .
                    "📂 Categoría: *{$category}* \n\n" .
                    "💳 *Pagar aquí:* \n{$paymentLink} \n\n" .
                    "🚀 *Entrar al Panel:* \n{$magicLink}";

                Log::info("4. Enviando mensaje de respuesta...");
                $this->sendWhatsApp($phone, $mensaje);
            } else {
                Log::info("2. Ticket existente encontrado: #{$ticket->id}");
                // --- TICKET EXISTENTE ---
                $message = Message::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'body' => $text,
                    'source' => 'whatsapp'
                ]);

                try {
                    broadcast(new MessageSent($message));
                } catch (\Exception $e) {
                    Log::error("Error Websocket: " . $e->getMessage());
                }
            }

            return response()->json(['status' => 'processed']);
        } catch (\Exception $e) {
            // Este catch atrapa cualquier error fatal y nos dice qué pasó
            Log::error("🔥 ERROR CRÍTICO EN WEBHOOK: " . $e->getMessage());
            Log::error($e->getTraceAsString());
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

        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text]
        ]);

        if ($response->successful()) {
            Log::info("✅ WhatsApp enviado correctamente.");
        } else {
            Log::error("❌ Error Meta API: " . $response->body());
        }
    }
}
