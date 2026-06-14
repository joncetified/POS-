<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dapur - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #fff3ec; --surface: #fffdf9; --ink: #2b211d; --muted: #927f73; --line: #efd7c9; --accent: #ff7a3d; --brown: #5b2a0f; --green: #287967; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input { font: inherit; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1320px, calc(100% - 32px)); margin: 22px auto; display: grid; gap: 16px; }
        .topbar, .panel, .order-card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 18px 38px rgba(91, 42, 15, .08); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; align-items: center; padding: 16px 18px; }
        .topbar h1 { margin: 0; font-size: clamp(1.65rem, 3vw, 2.45rem); }
        .muted { color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
        .btn { min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 13px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #fff8f2; color: var(--brown); font-weight: 900; cursor: pointer; }
        .btn.primary { border-color: var(--accent); background: var(--accent); color: #fff; }
        .btn.active, .btn.speaking { border-color: var(--brown); background: var(--brown); color: #fff; }
        .btn:disabled { opacity: .56; cursor: not-allowed; }
        .panel { padding: 18px; display: grid; gap: 16px; }
        .orders { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .order-card { padding: 16px; display: grid; gap: 14px; }
        .order-head { display: flex; align-items: start; justify-content: space-between; gap: 12px; }
        .order-head h2 { margin: 0; font-size: 1.35rem; }
        .badge { border-radius: 999px; padding: 6px 10px; background: #fff0e8; color: var(--accent); font-weight: 950; }
        .items { display: grid; gap: 8px; }
        .item-row { display: grid; grid-template-columns: 58px minmax(0, 1fr) auto; gap: 10px; align-items: center; border: 1px solid var(--line); border-radius: 8px; padding: 8px; background: #fffaf6; }
        .thumb { width: 58px; aspect-ratio: 1; border-radius: 8px; display: grid; place-items: center; overflow: hidden; background: #ffd4bd; color: var(--brown); font-weight: 950; }
        .thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .item-row strong, .item-row span { overflow-wrap: anywhere; }
        .qty { min-width: 46px; text-align: center; border-radius: 999px; padding: 7px 9px; background: var(--brown); color: #fff; font-weight: 950; }
        .note { border: 1px dashed var(--line); border-radius: 8px; padding: 11px; background: #fff8f2; color: var(--brown); line-height: 1.45; }
        .empty { min-height: 260px; display: grid; place-items: center; border: 1px dashed var(--line); border-radius: 8px; color: var(--muted); text-align: center; }
        .toast { position: fixed; left: 50%; bottom: 18px; z-index: 20; max-width: calc(100% - 28px); transform: translateX(-50%) translateY(80px); opacity: 0; border-radius: 999px; background: var(--ink); color: #fff; padding: 12px 16px; transition: 180ms ease; font-weight: 850; }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        @media (max-width: 940px) { .topbar { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } .orders { grid-template-columns: 1fr; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Dapur</h1>
                <p class="muted">Pesanan dari menu pelanggan dan open bill kasir masuk ke sini untuk koki.</p>
            </div>
            @include('partials.staff-nav')
        </section>

        <section class="panel">
            <div class="actions">
                <button id="speak-all" class="btn primary" type="button">Dengar Semua Pesanan</button>
                <button class="btn" type="button" onclick="window.location.reload()">Refresh</button>
            </div>

            @if ($orders->isEmpty())
                <div class="empty">Belum ada pesanan aktif untuk dapur.</div>
            @else
                <div class="orders">
                    @foreach ($orders as $order)
                        @php
                            $speechLines = [
                                $order->table_number ? 'Meja ' . $order->table_number : 'Tanpa nomor meja',
                                $order->customer_name ? 'Pelanggan ' . $order->customer_name : 'Pelanggan umum',
                            ];

                            foreach ($order->items as $item) {
                                $speechLines[] = $item->product_name . ' jumlah ' . $item->quantity;
                            }

                            if ($order->customer_note) {
                                $speechLines[] = 'Catatan: ' . $order->customer_note;
                            }
                        @endphp
                        <article class="order-card" data-order-card data-speech="{{ e(implode('. ', $speechLines)) }}">
                            <div class="order-head">
                                <div>
                                    <h2>{{ $order->table_number ? 'Meja ' . $order->table_number : 'Order ' . $order->id }}</h2>
                                    <p class="muted">{{ $order->customer_name ?: 'Umum' }} - {{ $order->updated_at->timezone('Asia/Jakarta')->format('H:i') }}</p>
                                </div>
                                <span class="badge">{{ strtoupper($order->status) }}</span>
                            </div>

                            <div class="items">
                                @foreach ($order->items as $item)
                                    <div class="item-row">
                                        <span class="thumb">
                                            @if ($item->product?->imageUrl())
                                                <img src="{{ $item->product->imageUrl() }}" alt="{{ $item->product_name }}">
                                            @else
                                                {{ collect(explode(' ', $item->product_name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}
                                            @endif
                                        </span>
                                        <div>
                                            <strong>{{ $item->product_name }}</strong>
                                            @if ($item->product?->package_contents)
                                                <br><span class="muted">{{ $item->product->package_contents }}</span>
                                            @endif
                                        </div>
                                        <span class="qty">x{{ $item->quantity }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($order->customer_note)
                                <div class="note"><strong>Catatan:</strong> {{ $order->customer_note }}</div>
                            @endif

                            <button class="btn primary" type="button" data-speak-order>Dengar Pesanan</button>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script>
        const canSpeak = 'speechSynthesis' in window && typeof SpeechSynthesisUtterance !== 'undefined';
        let activeButton = null;

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            window.setTimeout(() => toast.classList.remove('show'), 2200);
        }

        function getIndonesianVoice() {
            if (!canSpeak || typeof window.speechSynthesis.getVoices !== 'function') {
                return null;
            }

            const voices = window.speechSynthesis.getVoices();
            return voices.find((voice) => voice.lang === 'id-ID')
                || voices.find((voice) => voice.lang?.toLowerCase().startsWith('id'))
                || null;
        }

        function stopSpeech() {
            if (canSpeak) {
                window.speechSynthesis.cancel();
            }

            if (activeButton) {
                activeButton.classList.remove('speaking');
            }

            activeButton = null;
        }

        function speakText(text, button = null) {
            if (!canSpeak) {
                showToast('Browser tidak mendukung text-to-voice');
                return;
            }

            if (window.speechSynthesis.speaking) {
                stopSpeech();
                if (activeButton === button) return;
            }

            const cleanText = String(text || '').trim();
            if (!cleanText) {
                showToast('Tidak ada pesanan untuk dibacakan');
                return;
            }

            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'id-ID';
            const voice = getIndonesianVoice();
            if (voice) utterance.voice = voice;
            utterance.rate = 0.92;
            utterance.pitch = 1;
            utterance.onend = stopSpeech;
            utterance.onerror = () => {
                stopSpeech();
                showToast('Pesanan gagal dibacakan');
            };

            activeButton = button;
            if (activeButton) activeButton.classList.add('speaking');
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utterance);
        }

        document.querySelectorAll('[data-speak-order]').forEach((button) => {
            button.disabled = !canSpeak;
            button.title = canSpeak ? '' : 'Browser tidak mendukung text-to-voice';
            button.addEventListener('click', () => {
                speakText(button.closest('[data-order-card]')?.dataset.speech || '', button);
            });
        });

        const speakAll = document.getElementById('speak-all');
        speakAll.disabled = !canSpeak || !document.querySelector('[data-order-card]');
        speakAll.addEventListener('click', () => {
            const allText = Array.from(document.querySelectorAll('[data-order-card]'))
                .map((card) => card.dataset.speech)
                .filter(Boolean)
                .join('. Pesanan berikutnya. ');
            speakText(allText, speakAll);
        });

        window.addEventListener('beforeunload', stopSpeech);
    </script>
</body>
</html>
