<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $phoneId;
    protected $baseUrl = 'https://graph.facebook.com/v18.0/';

    public function __construct()
    {
        $this->token = env('META_WHATSAPP_TOKEN');
        $this->phoneId = env('META_PHONE_ID');
    }

    public function sendMessage($to, $message)
    {
        $url = $this->baseUrl . $this->phoneId . '/messages';

        try {
            $response = Http::withToken($this->token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message]
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Send Error: " . $e->getMessage());
            return null;
        }
    }
}
