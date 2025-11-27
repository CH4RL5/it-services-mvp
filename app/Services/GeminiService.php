<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function classifyTicket(string $description): string
    {
        if (!$this->apiKey) return 'General';

        // Prompt simple para clasificar
        $prompt = "Clasifica este problema en UNA palabra (DNS, Servidores, Correo, Hosting, Otros): \n\nProblema: \"$description\"";

        try {
            $response = Http::post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            $json = $response->json();
            $category = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Otros';

            return trim(str_replace(["\n", "."], "", $category));
        } catch (\Exception $e) {
            Log::error("Gemini Error: " . $e->getMessage());
            return 'Otros';
        }
    }
}
