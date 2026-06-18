<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    public function chat(array $messages): string
    {
        try {
            $response = Http::withToken(config('services.groq.key'))
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'    => config('services.groq.model'),
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $e) {
            // Gagal terhubung / timeout ke server Groq
            logger()->error('Groq connection error', ['message' => $e->getMessage()]);
            return 'Mohon Maaf, LLM sedang bermasalah. Silahkan Coba Beberapa Saat Lagi';
        }

        // Berhasil
        if ($response->successful()) {
            return $response->json('choices.0.message.content')
                ?? 'Maaf, aku tidak punya balasan untuk itu.';
        }

        $status = $response->status();

        // Kuota habis (rate limit terlampaui)
        if ($status === 429) {
            $retryAfter = $response->header('retry-after');
            logger()->warning('Groq rate limit reached', [
                'retry_after' => $retryAfter,
                'body'        => $response->body(),
            ]);

            $tunggu = $this->formatRetry($retryAfter);

            return "Maaf, aku sudah banyak menerima banyak permintaan"
                . "Silakan coba lagi {$tunggu}. Terima kasih atas pengertiannya";
        }

        // API key salah / tidak valid
        if ($status === 401) {
            logger()->error('Groq invalid API key', ['body' => $response->body()]);
            return 'Maaf, ada masalah konfigurasi di server ke LLM. Silahkan Cek terlebih dahulu';
        }

        // Error lain dari Groq
        logger()->error('Groq API error', [
            'status' => $status,
            'body'   => $response->body(),
        ]);
        $pesanError = $response->json('error.message') ?? 'Terjadi kesalahan tidak dikenal';
        return "Mohon Maaf, terjadi kendala saat memproses pesanmu berikut kendala nya {$pesanError}";
    }

    /**
     * Ubah nilai header retry-after (detik) jadi teks ramah.
     */
    private function formatRetry(?string $retryAfter): string
    {
        if (!$retryAfter || !is_numeric($retryAfter)) {
            return 'beberapa saat lagi';
        }

        $detik = (int) $retryAfter;

        if ($detik < 60) {
            return "dalam {$detik} detik";
        }

        $menit = (int) ceil($detik / 60);
        if ($menit < 60) {
            return "dalam sekitar {$menit} menit";
        }

        $jam = (int) ceil($menit / 60);
        return "dalam sekitar {$jam} jam";
    }
}