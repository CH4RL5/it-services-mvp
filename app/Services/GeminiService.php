<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    // USAMOS EL MODELO QUE SÍ TIENES EN TU LISTA:
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function classifyTicket(string $description): string
    {
        if (!$this->apiKey) {
            return 'General';
        }

        $prompt = "Actúa como un sistema clasificador de IT. Tu única tarea es leer el problema y responder con UNA sola palabra de esta lista: DNS, Servidores, Correo, Hosting, Otros. \n\nProblema: \"$description\" \n\nCategoría:";

        try {
            // withoutVerifying() es el parche para que funcione en tu Windows local
            $response = Http::withoutVerifying()->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            $json = $response->json();

            // Si hay error, lo logueamos pero no rompemos la app
            if (isset($json['error'])) {
                Log::error("Gemini API Error: " . $json['error']['message']);
                return 'Otros';
            }

            $category = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Otros';

            return trim(str_replace(["\n", ".", "*"], "", $category));
        } catch (\Exception $e) {
            Log::error("Gemini Connection Error: " . $e->getMessage());
            return 'Otros';
        }
    }
}
