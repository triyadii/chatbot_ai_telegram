<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Chatbot Telegram') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --tg-blue: #2CA5E0;
            --tg-dark: #1A1F2E;
            --tg-card: #242B3D;
            --tg-border: #2E3650;
            --tg-text: #E8EAF0;
            --tg-muted: #8B95A5;
            --tg-green: #4CAF50;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--tg-dark);
            color: var(--tg-text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Navbar */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--tg-border);
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(26, 31, 46, 0.96);
            backdrop-filter: blur(10px);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--tg-text);
            text-decoration: none;
        }

        .nav-links { display: flex; align-items: center; gap: 0.75rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-ghost { color: var(--tg-muted); }
        .btn-ghost:hover { color: var(--tg-text); border-color: var(--tg-border); }

        .btn-outline { color: var(--tg-blue); border-color: var(--tg-blue); }
        .btn-outline:hover { background: var(--tg-blue); color: #fff; }

        .btn-primary { background: var(--tg-blue); color: #fff; border-color: var(--tg-blue); }
        .btn-primary:hover { background: #1a94d0; border-color: #1a94d0; }

        /* Hero */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 5rem 1.5rem 3rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(44, 165, 224, 0.12);
            border: 1px solid rgba(44, 165, 224, 0.3);
            color: var(--tg-blue);
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--tg-green);
            animation: blink 2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.25rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
        }

        .highlight {
            background: linear-gradient(135deg, var(--tg-blue) 0%, #7B9EFF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.0625rem;
            color: var(--tg-muted);
            line-height: 1.7;
            max-width: 560px;
            margin-bottom: 2.5rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.875rem;
            justify-content: center;
        }

        .btn-lg {
            padding: 0.75rem 1.75rem;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            font-weight: 600;
        }

        /* Chat Preview */
        .chat-preview {
            max-width: 400px;
            width: calc(100% - 3rem);
            margin: 3rem auto;
            background: var(--tg-card);
            border: 1px solid var(--tg-border);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(0,0,0,0.45);
        }

        .chat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--tg-border);
            background: rgba(255,255,255,0.03);
        }

        .bot-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--tg-blue), #7B9EFF);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
        }

        .bot-name { font-size: 0.9375rem; font-weight: 600; }

        .bot-status {
            font-size: 0.75rem;
            color: var(--tg-green);
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 1px;
        }

        .bot-status::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--tg-green);
            display: inline-block;
        }

        .chat-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            min-height: 200px;
        }

        .msg-group { display: flex; flex-direction: column; }
        .msg-group.user { align-items: flex-end; }
        .msg-group.bot { align-items: flex-start; }

        .msg {
            max-width: 80%;
            padding: 0.625rem 0.875rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            line-height: 1.55;
        }

        .msg-bot {
            background: rgba(44, 165, 224, 0.14);
            border: 1px solid rgba(44, 165, 224, 0.2);
            color: var(--tg-text);
            border-bottom-left-radius: 0.25rem;
        }

        .msg-user {
            background: var(--tg-blue);
            color: #fff;
            border-bottom-right-radius: 0.25rem;
        }

        .msg-time {
            font-size: 0.6875rem;
            color: var(--tg-muted);
            margin-top: 0.2rem;
            padding: 0 0.125rem;
        }

        .typing {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.625rem 0.875rem;
            background: rgba(44, 165, 224, 0.1);
            border: 1px solid rgba(44, 165, 224, 0.15);
            border-radius: 1rem;
            border-bottom-left-radius: 0.25rem;
        }

        .typing span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--tg-blue);
            animation: dot 1.2s infinite;
        }

        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes dot {
            0%, 100% { opacity: 0.3; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-3px); }
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid var(--tg-border);
        }

        /* Section */
        .section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 4rem 1.5rem;
        }

        .section-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--tg-blue);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.625rem;
            text-align: center;
        }

        .section-title {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.625rem;
            letter-spacing: -0.02em;
        }

        .section-desc {
            color: var(--tg-muted);
            text-align: center;
            font-size: 0.9375rem;
            line-height: 1.7;
            max-width: 500px;
            margin: 0 auto 2.75rem;
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.125rem;
        }

        .feature-card {
            background: var(--tg-card);
            border: 1px solid var(--tg-border);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: border-color 0.2s, transform 0.2s;
        }

        .feature-card:hover {
            border-color: rgba(44, 165, 224, 0.45);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 44px;
            height: 44px;
            border-radius: 0.75rem;
            background: rgba(44, 165, 224, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.375rem;
        }

        .feature-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 0.45rem; }
        .feature-card p { font-size: 0.875rem; color: var(--tg-muted); line-height: 1.65; }

        /* Tech */
        .tech-section {
            border-top: 1px solid var(--tg-border);
            padding: 3.5rem 1.5rem 4rem;
            text-align: center;
        }

        .tech-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.875rem;
            margin-top: 2.25rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .tech-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--tg-card);
            border: 1px solid var(--tg-border);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--tg-muted);
        }

        /* CTA */
        .cta {
            text-align: center;
            padding: 4rem 1.5rem;
            border-top: 1px solid var(--tg-border);
        }

        .cta h2 { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700; margin-bottom: 0.75rem; }
        .cta p { color: var(--tg-muted); font-size: 0.9375rem; margin-bottom: 2rem; }

        /* Footer */
        footer {
            border-top: 1px solid var(--tg-border);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        footer p { font-size: 0.8125rem; color: var(--tg-muted); }

        @media (max-width: 600px) {
            nav { padding: 1rem 1.25rem; }
            .nav-brand span { display: none; }
            .hero { padding: 3rem 1.25rem 2rem; }
            footer { flex-direction: column; align-items: center; text-align: center; }
        }
    </style>
</head>
<body>

    {{-- Navbar --}}
    <nav>
        <a class="nav-brand" href="#">
            <svg width="28" height="28" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="tg-grad" x1="120" y1="0" x2="120" y2="240" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="#2AABEE"/>
                        <stop offset="1" stop-color="#229ED9"/>
                    </linearGradient>
                </defs>
                <circle cx="120" cy="120" r="120" fill="url(#tg-grad)"/>
                <path d="M176 70L152 178c-1.7 7.4-6 9.2-12.2 5.7L104 159l-17.2 16.5c-1.9 1.9-3.5 3.5-7.2 3.5l2.6-36.4 66.4-60c2.9-2.6-.6-4-4.5-1.4L61.4 145.2 27.2 134.5c-7.4-2.3-7.5-7.4 1.6-10.9L165 64c6.2-2.3 11.7 1.5 11 10z" fill="white"/>
            </svg>
            <span>{{ config('app.name', 'Chatbot Telegram') }}</span>
        </a>

        <div class="nav-links">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-ghost">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline">Daftar</a>
                    @endif
                @endauth
            @endif
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero">
        <div class="badge">
            <span class="badge-dot"></span>
            Powered by Groq AI &middot; Online 24/7
        </div>

        <h1>
            Asisten Cerdas di<br>
            <span class="highlight">Telegram</span> Kamu
        </h1>

        <p>
            Chatbot AI yang siap menjawab pertanyaan, membantu mengolah informasi,
            dan menemani percakapan kapan saja &mdash; langsung dari Telegram tanpa perlu aplikasi tambahan.
        </p>

        <div class="hero-actions">
            <a href="https://t.me/dii_agent_bot" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg">
                <svg width="17" height="17" viewBox="0 0 240 240" fill="none">
                    <path d="M176 70L152 178c-1.7 7.4-6 9.2-12.2 5.7L104 159l-17.2 16.5c-1.9 1.9-3.5 3.5-7.2 3.5l2.6-36.4 66.4-60c2.9-2.6-.6-4-4.5-1.4L61.4 145.2 27.2 134.5c-7.4-2.3-7.5-7.4 1.6-10.9L165 64c6.2-2.3 11.7 1.5 11 10z" fill="white"/>
                </svg>
                Mulai Chat Sekarang
            </a>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline btn-lg">Buka Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline btn-lg">Kelola Bot</a>
                @endauth
            @endif
        </div>
    </section>

    {{-- Chat Preview --}}
    <div class="chat-preview">
        <div class="chat-header">
            <div class="bot-avatar">🤖</div>
            <div>
                <div class="bot-name">{{ config('app.name', 'Chatbot AI') }}</div>
                <div class="bot-status">Online</div>
            </div>
        </div>

        <div class="chat-body">
            <div class="msg-group bot">
                <div class="msg msg-bot">Halo! Saya asisten AI yang siap membantu kamu. Ada yang bisa saya bantu hari ini? 😊</div>
                <div class="msg-time">10:30</div>
            </div>

            <div class="msg-group user">
                <div class="msg msg-user">Tolong jelaskan cara kerja machine learning</div>
                <div class="msg-time">10:31</div>
            </div>

            <div class="msg-group bot">
                <div class="msg msg-bot">Tentu! Machine learning adalah cabang AI di mana komputer belajar dari data secara otomatis tanpa diprogram secara eksplisit untuk setiap tugas...</div>
                <div class="msg-time">10:31</div>
            </div>

            <div class="typing">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <hr class="divider" style="max-width: 160px; margin: 0 auto; border-color: rgba(44,165,224,0.2);">

    {{-- Features --}}
    <section class="section">
        <p class="section-label">Fitur Unggulan</p>
        <h2 class="section-title">Semua yang Kamu Butuhkan</h2>
        <p class="section-desc">Dirancang untuk memberikan pengalaman percakapan yang natural, cepat, dan cerdas langsung di Telegram.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🧠</div>
                <h3>AI Generatif</h3>
                <p>Didukung Groq AI dengan LLM terkini untuk jawaban yang cerdas, kontekstual, dan akurat setiap saat.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Respons Super Cepat</h3>
                <p>Groq inference engine memastikan respons dalam hitungan milidetik, tanpa lag yang mengganggu percakapan.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🌙</div>
                <h3>Tersedia 24/7</h3>
                <p>Bot aktif sepanjang waktu. Kirim pesan kapan saja dan di mana saja, bot selalu siap merespons.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Konteks Percakapan</h3>
                <p>Mengingat konteks dalam satu sesi sehingga jawaban selalu relevan dan berkesinambungan.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🇮🇩</div>
                <h3>Bahasa Indonesia</h3>
                <p>Dioptimalkan untuk percakapan dalam Bahasa Indonesia agar komunikasi terasa natural dan mudah dipahami.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Aman & Terpercaya</h3>
                <p>Dibangun di atas infrastruktur Laravel yang solid dengan keamanan berlapis untuk melindungi data pengguna.</p>
            </div>
        </div>
    </section>

    {{-- Tech Stack --}}
    <div class="tech-section">
        <p class="section-label">Teknologi</p>
        <h2 class="section-title" style="margin-bottom: 0.5rem;">Dibangun dengan Stack Modern</h2>
        <p style="color: var(--tg-muted); font-size: 0.9375rem;">Kombinasi teknologi terpercaya untuk performa dan keandalan terbaik.</p>
        <div class="tech-row">
            <div class="tech-pill">
                <svg width="15" height="15" viewBox="0 0 50 52" fill="#FF2D20"><path d="M49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1-.402.694l-9.209 5.302V39.25c0 .286-.152.55-.4.694L20.42 51.01c-.044.025-.092.041-.14.057-.016.005-.031.013-.047.017a.808.808 0 0 1-.41 0c-.018-.004-.034-.014-.052-.02-.044-.014-.09-.03-.132-.054L.402 39.944A.801.801 0 0 1 0 39.25V6.334c0-.072.01-.142.028-.209.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.022.055-.047.088-.064h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.017.057.04.086.061.026.02.055.037.078.06.028.028.048.06.071.093.018.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.068a.809.809 0 0 1 .028.209v20.559l8.008-4.611v-10.51c0-.07.01-.141.028-.208.007-.024.02-.045.028-.068.016-.042.03-.085.052-.124.015-.026.037-.047.054-.071.024-.032.044-.065.072-.093.023-.023.052-.04.078-.06.03-.022.055-.047.087-.061h.002l9.61-5.533a.801.801 0 0 1 .8 0l9.61 5.533c.034.018.06.043.09.065.025.019.054.036.076.058.03.03.05.062.072.094.017.024.04.045.054.07.023.04.037.083.053.127.008.023.022.043.028.066zm-1.571 10.718v-9.124l-3.363 1.936-4.646 2.675v9.124l8.01-4.611zm-9.61 16.505v-9.13l-4.57 2.61-13.05 7.448v9.216l17.62-10.144zM1.602 7.719v31.068L19.22 48.93v-9.214l-9.204-5.209-.003-.002-.004-.002c-.031-.018-.057-.044-.086-.066-.025-.02-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-21.483L4.965 9.654 1.602 7.72zm8.81-5.994L2.405 6.334l8.005 4.609 8.006-4.61-8.006-4.608zm4.164 28.764l4.645-2.674V7.719l-3.363 1.936-4.646 2.675v20.096l3.364-1.937zM39.243 7.103l-8.006 4.609 8.006 4.609 8.005-4.61-8.005-4.608zm-.801 10.605l-4.646-2.675-3.363-1.936v9.124l4.645 2.674 3.364 1.937v-9.124zM20.02 38.33l11.743-6.704 5.87-3.35-8-4.606-9.211 5.303-8.395 4.833 7.993 4.524z"/></svg>
                Laravel 12
            </div>
            <div class="tech-pill">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="#2CA5E0"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                Telegram Bot API
            </div>
            <div class="tech-pill">⚡ Groq AI</div>
            <div class="tech-pill">🐘 PHP 8.x</div>
            <div class="tech-pill">🗄️ MySQL</div>
        </div>
    </div>

    {{-- CTA --}}
    <section class="cta">
        <h2>Coba Sekarang, Gratis!</h2>
        <p>Tidak perlu instalasi apapun. Cukup buka Telegram dan mulai ngobrol.</p>
        <a href="https://t.me/dii_agent_bot" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg">
            <svg width="17" height="17" viewBox="0 0 240 240" fill="none">
                <path d="M176 70L152 178c-1.7 7.4-6 9.2-12.2 5.7L104 159l-17.2 16.5c-1.9 1.9-3.5 3.5-7.2 3.5l2.6-36.4 66.4-60c2.9-2.6-.6-4-4.5-1.4L61.4 145.2 27.2 134.5c-7.4-2.3-7.5-7.4 1.6-10.9L165 64c6.2-2.3 11.7 1.5 11 10z" fill="white"/>
            </svg>
            Buka di Telegram
        </a>
    </section>

    {{-- Footer --}}
    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Chatbot Telegram') }}. Dibuat dengan Laravel &amp; Groq AI.</p>
        <p>v{{ app()->version() }}</p>
    </footer>

</body>
</html>
