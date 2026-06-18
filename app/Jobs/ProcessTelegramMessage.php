<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\GroqService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $chatId,
        public string $text,
    ) {}

    public function handle(GroqService $groq): void
    {
        // 1. Simpan pesan user
        Message::create([
            'chat_id' => $this->chatId,
            'role'    => 'user',
            'content' => $this->text,
        ]);

        // 2. Ambil 20 pesan terakhir sebagai konteks percakapan
        $history = Message::where('chat_id', $this->chatId)
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        // 3. Sisipkan system prompt di awal
        array_unshift($history, [
            'role'    => 'system',
            'content' => 'Kamu adalah asisten yang ramah, membantu, dan selalu menjawab dalam bahasa Indonesia yang jelas.',
        ]);

        // 4. Panggil LLM
        $reply = $groq->chat($history);

        // 5. Simpan balasan
        Message::create([
            'chat_id' => $this->chatId,
            'role'    => 'assistant',
            'content' => $reply,
        ]);

        // 6. Kirim balasan ke user lewat Telegram
        $this->sendTelegram($this->chatId, $reply);
    }

    private function sendTelegram(int $chatId, string $text): void
    {
        Http::post(
            'https://api.telegram.org/bot' . config('services.telegram.token') . '/sendMessage',
            [
                'chat_id' => $chatId,
                'text'    => $text,
            ]
        );
    }
}