# Panduan Lengkap: Chatbot Telegram dengan Laravel + Groq

Panduan ini menuntun kamu dari nol sampai bot berjalan. Ikuti berurutan.

**Yang akan kamu buat:** Bot Telegram yang menjawab pakai LLM open-source (Llama) lewat Groq, dengan riwayat percakapan tersimpan di database, dan proses LLM dijalankan di queue agar anti-timeout.

**Alur sistem:**

```
User → Telegram → Webhook → Laravel → Queue Job → Groq (LLM) → balasan → Telegram → User
```

---

## Daftar Isi

1. [Persiapan akun & tool](#1-persiapan-akun--tool)
2. [Membuat bot Telegram](#2-membuat-bot-telegram)
3. [Mendapatkan API key Groq](#3-mendapatkan-api-key-groq)
4. [Membuat project Laravel](#4-membuat-project-laravel)
5. [Konfigurasi environment](#5-konfigurasi-environment)
6. [Membuat database & migration](#6-membuat-database--migration)
7. [Membuat Model](#7-membuat-model)
8. [Membuat GroqService](#8-membuat-groqservice)
9. [Membuat Queue Job](#9-membuat-queue-job)
10. [Membuat Controller webhook](#10-membuat-controller-webhook)
11. [Mendaftarkan route & mematikan CSRF](#11-mendaftarkan-route--mematikan-csrf)
12. [Menjalankan secara lokal dengan ngrok](#12-menjalankan-secara-lokal-dengan-ngrok)
13. [Mendaftarkan webhook ke Telegram](#13-mendaftarkan-webhook-ke-telegram)
14. [Menjalankan & menguji](#14-menjalankan--menguji)
15. [Menambah perintah /start dan /reset](#15-menambah-perintah-start-dan-reset)
16. [Troubleshooting](#16-troubleshooting)
17. [Langkah ke production](#17-langkah-ke-production)

---

## 1. Persiapan akun & tool

Pastikan sudah terpasang di komputermu:

- **PHP 8.2+** dan **Composer** — cek dengan `php -v` dan `composer -V`
- **Laravel installer** (opsional) — `composer global require laravel/installer`
- **MySQL** — pastikan server MySQL berjalan dan kamu bisa membuat database. Cek dengan `mysql --version`.
- **ngrok** — untuk membuat URL publik HTTPS saat development. Daftar gratis di [ngrok.com](https://ngrok.com) lalu install.
- Akun **Telegram** dan akun **Groq**

---

## 2. Membuat bot Telegram

1. Buka Telegram, cari **@BotFather** (centang biru resmi).
2. Kirim `/newbot`.
3. Beri nama bot (mis. `Asisten Belajarku`).
4. Beri username yang diakhiri `bot` (mis. `asisten_belajarku_bot`).
5. BotFather membalas dengan **token**, contoh:
   ```
   7123456789:AAH-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```
6. **Simpan token ini.** Ini kunci untuk mengontrol botmu.

---

## 3. Mendapatkan API key Groq

1. Buka [console.groq.com](https://console.groq.com) dan daftar/masuk.
2. Masuk ke menu **API Keys** → **Create API Key**.
3. Beri nama, lalu salin key yang muncul (diawali `gsk_`).
4. **Simpan key ini sekarang** — Groq hanya menampilkannya sekali.

> Tier gratis Groq punya batas request per menit & token per hari. Cukup untuk belajar. Pantau pemakaian di dashboard.

---

## 4. Membuat project Laravel

```bash
laravel new chatbot
cd chatbot
```

Atau tanpa Laravel installer:

```bash
composer create-project laravel/laravel chatbot
cd chatbot
```

Coba jalankan untuk memastikan berhasil:

```bash
php artisan serve
```

Buka `http://localhost:8000` — harus muncul halaman default Laravel. Hentikan dulu dengan `Ctrl+C`.

---

## 5. Konfigurasi environment

Buka file `.env` di root project, tambahkan baris berikut (sesuaikan nilainya):

```env
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxx
GROQ_MODEL=llama-3.3-70b-versatile
TELEGRAM_BOT_TOKEN=7123456789:AAH-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Pertama, buat database kosong di MySQL. Masuk ke MySQL lalu jalankan:

```sql
CREATE DATABASE chatbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

> `utf8mb4` penting agar emoji dan karakter non-latin tersimpan dengan benar.

Lalu atur koneksi MySQL di `.env` (sesuaikan user & password milikmu):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatbot
DB_USERNAME=root
DB_PASSWORD=password_mysql_kamu
```

Atur queue agar pakai database (untuk job antrian nanti):

```env
QUEUE_CONNECTION=database
```

Sekarang daftarkan konfigurasi custom. Buka `config/services.php`, tambahkan di dalam array:

```php
'groq' => [
    'key'   => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
],

'telegram' => [
    'token' => env('TELEGRAM_BOT_TOKEN'),
],
```

---

## 6. Membuat database & migration

Buat migration untuk tabel penyimpan pesan:

```bash
php artisan make:migration create_messages_table
```

Buka file yang baru dibuat di `database/migrations/..._create_messages_table.php`, isi method `up()`:

```php
public function up(): void
{
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('chat_id')->index();   // ID chat Telegram
        $table->string('role');                    // 'user' atau 'assistant'
        $table->text('content');                   // isi pesan
        $table->timestamps();
    });
}
```

Buat juga tabel untuk queue (jika belum ada di versi Laravel kamu):

```bash
php artisan make:queue-table
```

> Pada Laravel 11/12 perintahnya `php artisan make:queue-table`. Jika tidak tersedia, migration `jobs` biasanya sudah disertakan secara default.

Jalankan semua migration:

```bash
php artisan migrate
```

---

## 7. Membuat Model

```bash
php artisan make:model Message
```

Buka `app/Models/Message.php`, izinkan kolom diisi massal:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['chat_id', 'role', 'content'];
}
```

---

## 8. Membuat GroqService

Service ini bertugas memanggil API Groq. Buat file `app/Services/GroqService.php` (buat folder `Services` jika belum ada):

```php
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
```

Catatan: Groq memakai format kompatibel OpenAI, sehingga header pakai `Bearer token` (lewat `withToken`) dan struktur balasan ada di `choices.0.message.content`.

---

## 9. Membuat Queue Job

Agar webhook Telegram langsung membalas cepat dan tidak timeout, proses pemanggilan LLM dipindah ke job antrian.

```bash
php artisan make:job ProcessTelegramMessage
```

Buka `app/Jobs/ProcessTelegramMessage.php`:

```php
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
```

---

## 10. Membuat Controller webhook

Controller ini menerima update dari Telegram, lalu melempar pekerjaan berat ke queue.

```bash
php artisan make:controller TelegramController
```

Buka `app/Http/Controllers/TelegramController.php`:

```php
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
```

---

## 11. Mendaftarkan route & mematikan CSRF

Telegram mengirim POST tanpa token CSRF, jadi route webhook harus dikecualikan dari proteksi CSRF.

Tambahkan route di `routes/web.php`:

```php
use App\Http\Controllers\TelegramController;

Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
```

Lalu kecualikan dari CSRF. Pada **Laravel 11/12**, buka `bootstrap/app.php` dan sesuaikan bagian `withMiddleware`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'telegram/webhook',
    ]);
})
```

> Pada Laravel 10 ke bawah, pengecualian ditaruh di `app/Http/Middleware/VerifyCsrfToken.php` pada properti `$except`.

---

## 12. Menjalankan secara lokal dengan ngrok

Webhook Telegram wajib URL publik **HTTPS**. Saat development, ngrok membuatkannya untukmu.

Jalankan Laravel di satu terminal:

```bash
php artisan serve
```

(berjalan di `http://localhost:8000`)

Buka terminal kedua, jalankan ngrok:

```bash
ngrok http 8000
```

ngrok menampilkan URL publik seperti:

```
Forwarding  https://a1b2-c3d4.ngrok-free.app -> http://localhost:8000
```

**Salin URL `https://...ngrok-free.app` itu.** URL ini berubah setiap kali ngrok di-restart (di versi gratis).

---

## 13. Mendaftarkan webhook ke Telegram

Beri tahu Telegram ke mana harus mengirim pesan. Ganti `<TOKEN>` dan URL ngrok dengan milikmu, jalankan di terminal:

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://a1b2-c3d4.ngrok-free.app/telegram/webhook"
```

Jika berhasil, balasannya:

```json
{"ok":true,"result":true,"description":"Webhook was set"}
```

Cek status webhook kapan saja:

```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

---

## 14. Menjalankan & menguji

Karena memakai queue, jalankan worker di terminal ketiga:

```bash
php artisan queue:work
```

Sekarang kamu punya tiga proses berjalan:
1. `php artisan serve` — server Laravel
2. `ngrok http 8000` — tunnel publik
3. `php artisan queue:work` — pemroses antrian

**Uji botnya:** buka Telegram, cari botmu (username yang dibuat di langkah 2), kirim `/start`, lalu kirim pesan apa pun. Bot akan membalas memakai Llama via Groq.

Jika tidak membalas, lihat bagian Troubleshooting di bawah.

---

## 15. Menambah perintah /start dan /reset

Sudah termasuk di Controller pada langkah 10:

- `/start` — menyapa pengguna baru
- `/reset` — menghapus riwayat percakapan pengguna tersebut

Kamu bisa menambah perintah lain dengan pola `if` yang sama, mis. `/help` untuk menampilkan bantuan.

---

## 16. Troubleshooting

**Bot tidak membalas sama sekali**
- Pastikan ketiga proses (serve, ngrok, queue:work) berjalan.
- Cek `getWebhookInfo` — perhatikan field `last_error_message`.
- Pastikan URL ngrok di webhook sama dengan yang sedang aktif (ngrok gratis ganti URL tiap restart — daftarkan ulang webhook bila ngrok di-restart).

**Webhook error "wrong response from the webhook"**
- Pastikan controller selalu mengembalikan `response()->json(['ok' => true])` dengan status 200.
- Lihat `storage/logs/laravel.log` untuk error PHP.

**Balasan selalu "terjadi kesalahan"**
- Cek `GROQ_API_KEY` benar dan masih aktif.
- Cek nama model di `GROQ_MODEL` masih tersedia (daftar model bisa berubah — lihat dashboard Groq).
- Lihat log: `tail -f storage/logs/laravel.log`.

**Job tidak jalan**
- Pastikan `QUEUE_CONNECTION=database` di `.env`.
- Pastikan `php artisan queue:work` berjalan.
- Setelah mengubah `.env`, jalankan `php artisan config:clear`.

**Perubahan .env tidak terbaca**
```bash
php artisan config:clear
php artisan cache:clear
```

**Error koneksi MySQL saat `migrate`**
- Pastikan server MySQL berjalan.
- Cek `DB_USERNAME` dan `DB_PASSWORD` di `.env` benar.
- Pastikan database `chatbot` sudah dibuat (langkah 5).
- Jika muncul `SQLSTATE[HY000] [2002] Connection refused`, biasanya `DB_HOST` salah — gunakan `127.0.0.1`, bukan `localhost`, jika MySQL berjalan di port standar.
- Setelah mengubah `.env`, jalankan `php artisan config:clear` lalu ulangi `php artisan migrate`.

---

## 17. Langkah ke production

Saat siap online 24 jam tanpa laptop menyala:

1. **Sewa server/VPS** (mis. DigitalOcean, Hetzner, atau hosting Laravel seperti Forge) atau platform seperti Railway/Render.
2. **Pakai domain ber-HTTPS** sungguhan, bukan ngrok. Daftarkan webhook ke domain itu.
3. **Jalankan queue worker permanen** dengan **Supervisor** agar otomatis hidup kembali bila mati:
   ```ini
   [program:chatbot-worker]
   command=php /path/ke/chatbot/artisan queue:work --tries=3
   autostart=true
   autorestart=true
   numprocs=1
   ```
4. **Amankan kredensial** — jangan pernah commit `.env` ke Git (default Laravel sudah mengabaikannya).
5. **Pantau rate limit Groq** dan tambahkan penanganan bila kuota habis.

---

## Pengembangan lanjutan (opsional)

- **RAG** — agar bot menjawab dari dokumen/datamu sendiri (simpan embedding di vector store seperti pgvector, Qdrant, atau Pinecone, lalu ambil potongan relevan sebelum memanggil LLM).
- **Streaming** — menampilkan jawaban bertahap (lebih kompleks di Telegram, perlu edit pesan berkala).
- **Pindah ke WhatsApp** — ganti layer Telegram dengan WhatsApp Cloud API (Meta) atau Twilio; inti logika (Service + Job) tetap sama.
- **Indikator "sedang mengetik"** — kirim `sendChatAction` dengan `typing` saat job diproses agar terasa responsif.

Selamat membangun! Ikuti langkah 1–14 secara berurutan dan botmu akan jalan.
