<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    /**
     * Kirim daftar pesan ke Groq dan kembalikan teks balasan.
     *
     * @param array $messages format: [['role' => 'user', 'content' => '...'], ...]
     */
    public function chat(array $messages): string
    {
        $response = Http::withToken(config('services.groq.key'))
            ->timeout(60)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'    => config('services.groq.model'),
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            logger()->error('Groq API error', ['body' => $response->body()]);
            return 'Maaf, terjadi kesalahan saat memproses pesanmu. Coba lagi ya.';
        }

        return $response->json('choices.0.message.content')
            ?? 'Maaf, aku tidak punya balasan untuk itu.';
    }
}