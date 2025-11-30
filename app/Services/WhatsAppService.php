<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send($to, $text)
    {
        // 1. Limpieza del número (El parche mexicano)
        // Si tiene 13 dígitos y empieza con 521, lo convertimos a 52
        if (strlen($to) == 13 && str_starts_with($to, '521')) {
            $to = '52' . substr($to, 3);
        }

        // 2. Configuración
        $token = env('META_WHATSAPP_TOKEN');
        $phoneId = env('META_PHONE_ID');
        $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

        // 3. Envío
        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text]
        ]);

        // 4. Logs para depuración
        if ($response->successful()) {
            Log::info("✅ WhatsApp enviado a {$to}");
            return true;
        } else {
            Log::error("❌ Error WhatsApp: " . $response->body());
            return false;
        }
    }
}
