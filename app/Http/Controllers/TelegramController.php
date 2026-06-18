<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $chatId = $request->input('message.chat.id');
        $text   = $request->input('message.text');

        // Abaikan update yang bukan pesan teks
        if (!$chatId || !$text) {
            return response()->json(['ok' => true]);
        }

        // Tangani perintah khusus
        if ($text === '/start') {
            $this->reply($chatId, 'Halo! Aku asisten AI-mu. Ketik apa saja untuk mulai mengobrol. Ketik /reset untuk menghapus riwayat percakapan.');
            return response()->json(['ok' => true]);
        }

        if ($text === '/reset') {
            \App\Models\Message::where('chat_id', $chatId)->delete();
            $this->reply($chatId, 'Riwayat percakapan sudah dihapus. Mari mulai dari awal!');
            return response()->json(['ok' => true]);
        }

        // Lempar ke queue agar webhook membalas cepat (anti-timeout)
        ProcessTelegramMessage::dispatch($chatId, $text);

        return response()->json(['ok' => true]);
    }

    private function reply(int $chatId, string $text): void
    {
        Http::post(
            'https://api.telegram.org/bot' . config('services.telegram.token') . '/sendMessage',
            ['chat_id' => $chatId, 'text' => $text]
        );
    }
}