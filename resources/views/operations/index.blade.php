<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operasional ERP - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .shell { width: min(1240px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel, .card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.5rem, 2.4vw, 2rem); }
        h2 { font-size: 1.12rem; }
        .muted { color: var(--muted); }
        .cards { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .card { padding: 16px; display: grid; gap: 8px; min-height: 112px; }
        .card strong { font-size: 1.22rem; overflow-wrap: anywhere; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
        .panel { padding: 18px; display: grid; gap: 14px; }
        .form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; align-items: end; }
        .field { display: grid; gap: 6px; }
        label { color: var(--muted); font-size: .76rem; font-weight: 850; text-transform: uppercase; }
        input, select, textarea { width: 100%; min-width: 0; max-width: 100%; min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--surface); color: var(--ink); }
        select { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .salary-form { grid-template-columns: minmax(240px, 1.2fr) minmax(130px, .7fr) minmax(130px, .7fr); }
        textarea { min-height: 42px; resize: vertical; }
        .voice-note-wrap { display: grid; gap: 8px; }
        .voice-note-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .voice-note-btn { min-height: 36px; border: 1px solid var(--line); border-radius: 8px; padding: 7px 10px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .voice-note-btn.is-listening { border-color: var(--primary); background: var(--primary); color: #fff; }
        .voice-note-btn.is-speaking { border-color: #334155; background: #334155; color: #fff; }
        .voice-note-btn:disabled { opacity: .56; cursor: not-allowed; }
        #voice-toast { position: fixed; right: 18px; bottom: 18px; z-index: 20; max-width: min(360px, calc(100% - 36px)); box-shadow: 0 14px 30px rgba(15, 23, 42, .14); }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: .76rem; text-transform: uppercase; }
        .table-wrap { overflow-x: auto; }
        .table-wrap table { min-width: 720px; }
        .logout-form { margin: 0; }
        @media (max-width: 1100px) { .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } .topbar, .grid, .form-grid, .salary-form { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } }
        @media (max-width: 560px) { .cards { grid-template-columns: 1fr; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
                <span class="staff-page-title">Operasional / ERP</span>
            </div>
            @include('partials.staff-nav')
        </section>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="cards">
            <article class="card"><p class="muted">Omzet bulan ini</p><strong>Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Biaya operasional</p><strong>Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Gaji terbayar</p><strong>Rp {{ number_format($summary['salaries'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Belanja stok</p><strong>Rp {{ number_format($summary['inventory_cost'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Estimasi profit</p><strong>Rp {{ number_format($summary['net'], 0, ',', '.') }}</strong></article>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Biaya Operasional</h2>
                <form class="form-grid" method="POST" action="{{ route('operations.expenses.store') }}">
                    @csrf
                    <div class="field"><label>Kategori</label><input name="category" placeholder="Listrik, sewa, bahan" required></div>
                    <div class="field"><label>Deskripsi</label><input name="description" required></div>
                    <div class="field"><label>Nominal</label><input name="amount" type="number" min="0" required></div>
                    <div class="field"><label>Tanggal</label><input name="spent_at" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label>Vendor</label><input name="vendor"></div>
                    <button class="btn primary" type="submit">Simpan Biaya</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th></tr></thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr><td>{{ $expense->spent_at->format('d/m/Y') }}</td><td>{{ $expense->category }}</td><td>{{ $expense->description }}<br><span class="muted">{{ $expense->vendor ?: '-' }}</span></td><td>Rp {{ number_format($expense->amount, 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="muted">Belum ada biaya operasional.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Karyawan & Gaji</h2>
                <form class="form-grid" method="POST" action="{{ route('operations.employees.store') }}">
                    @csrf
                    <div class="field"><label>Nama</label><input name="name" required></div>
                    <div class="field"><label>Jabatan</label><input name="position" value="Barista" required></div>
                    <div class="field"><label>Telepon</label><input name="phone"></div>
                    <div class="field"><label>Gaji Pokok</label><input name="base_salary" type="number" min="0" required></div>
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn primary" type="submit">Tambah Karyawan</button>
                </form>
                <form class="form-grid salary-form" method="POST" action="{{ route('operations.salaries.store') }}">
                    @csrf
                    <div class="field"><label>Karyawan</label><select name="employee_id" required>@foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->position }}</option>@endforeach</select></div>
                    <div class="field"><label>Periode</label><input name="period" type="month" value="{{ now()->format('Y-m') }}" required></div>
                    <div class="field"><label>Nominal</label><input name="amount" type="number" min="0" required></div>
                    <div class="field"><label>Tanggal Bayar</label><input name="paid_at" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="field">
                        <label>Catatan</label>
                        <div class="voice-note-wrap">
                            <input id="salary-note" name="note" maxlength="160">
                            <div class="voice-note-actions">
                                <button class="voice-note-btn" type="button" data-voice-listen="salary-note"><span aria-hidden="true">Mic</span><span>Voice Note</span></button>
                                <button class="voice-note-btn" type="button" data-voice-speak="salary-note"><span aria-hidden="true">TTS</span><span>Dengar Catatan</span></button>
                            </div>
                        </div>
                    </div>
                    <button class="btn primary" type="submit" @disabled($employees->isEmpty())>Catat Gaji</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Karyawan</th><th>Periode</th><th>Nominal</th><th>Tanggal</th></tr></thead>
                        <tbody>
                            @forelse ($salaryPayments as $payment)
                                <tr><td>{{ $payment->employee?->name ?: '-' }}</td><td>{{ $payment->period }}</td><td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td><td>{{ $payment->paid_at->format('d/m/Y') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="muted">Belum ada pembayaran gaji.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Inventory Movement</h2>
            <form class="form-grid" method="POST" action="{{ route('operations.inventory.store') }}">
                @csrf
                <div class="field"><label>Produk</label><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} - stok {{ $product->stock }} {{ $product->unit }}</option>@endforeach</select></div>
                <div class="field"><label>Tipe</label><select name="type" required><option value="in">Stock In</option><option value="out">Stock Out</option><option value="adjustment">Set Stok Aktual</option></select></div>
                <div class="field"><label>Qty</label><input name="quantity" type="number" min="1" required></div>
                <div class="field"><label>Harga Modal / Unit</label><input name="unit_cost" type="number" min="0" value="0"></div>
                <div class="field"><label>Tanggal</label><input name="occurred_at" type="date" value="{{ now()->toDateString() }}" required></div>
                <div class="field">
                    <label>Catatan</label>
                    <div class="voice-note-wrap">
                        <input id="inventory-note" name="note" maxlength="160">
                        <div class="voice-note-actions">
                            <button class="voice-note-btn" type="button" data-voice-listen="inventory-note"><span aria-hidden="true">Mic</span><span>Voice Note</span></button>
                            <button class="voice-note-btn" type="button" data-voice-speak="inventory-note"><span aria-hidden="true">TTS</span><span>Dengar Catatan</span></button>
                        </div>
                    </div>
                </div>
                <button class="btn primary" type="submit" @disabled($products->isEmpty())>Simpan Movement</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tanggal</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Stok</th><th>Biaya</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->occurred_at->format('d/m/Y') }}</td>
                                <td>{{ $movement->product?->name ?: '-' }}</td>
                                <td>{{ strtoupper($movement->type) }}</td>
                                <td>{{ $movement->quantity }}</td>
                                <td>{{ $movement->stock_before }} -> {{ $movement->stock_after }}</td>
                                <td>Rp {{ number_format($movement->total_cost, 0, ',', '.') }}</td>
                                <td>{{ $movement->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <div id="voice-toast" class="status" role="status" aria-live="polite" hidden></div>
    <script>
        const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition || null;
        const canSpeak = 'speechSynthesis' in window && typeof SpeechSynthesisUtterance !== 'undefined';
        let activeRecognition = null;
        let activeListenButton = null;
        let activeUtterance = null;
        let activeSpeakButton = null;

        function showVoiceToast(message) {
            const toast = document.getElementById('voice-toast');
            toast.textContent = message;
            toast.hidden = false;
            window.setTimeout(() => {
                toast.hidden = true;
            }, 2200);
        }

        function voiceTarget(button, type) {
            return document.getElementById(button.dataset[type]);
        }

        function setListenButton(button, listening) {
            if (!button) return;
            button.classList.toggle('is-listening', listening);
            button.setAttribute('aria-pressed', listening ? 'true' : 'false');
            const label = button.querySelector('span:last-child');
            if (label) label.textContent = listening ? 'Stop Rekam' : 'Voice Note';
        }

        function setSpeakButton(button, speaking) {
            if (!button) return;
            button.classList.toggle('is-speaking', speaking);
            button.setAttribute('aria-pressed', speaking ? 'true' : 'false');
            const label = button.querySelector('span:last-child');
            if (label) label.textContent = speaking ? 'Stop Suara' : 'Dengar Catatan';
        }

        function appendVoiceText(input, text) {
            const current = String(input.value || '').trim();
            const separator = current ? ' ' : '';
            const maxLength = Number(input.getAttribute('maxlength') || 160);
            input.value = `${current}${separator}${text}`.slice(0, maxLength);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function getSpeechRecognitionErrorMessage(error) {
            const messages = {
                'not-allowed': 'Izin mic ditolak. Izinkan microphone di browser.',
                'service-not-allowed': 'Voice-to-text diblokir browser.',
                'audio-capture': 'Mic tidak ditemukan atau sedang dipakai aplikasi lain.',
                'no-speech': 'Tidak ada suara terdengar. Coba bicara lebih dekat ke mic.',
                network: 'Voice-to-text gagal karena koneksi/browser.',
                aborted: 'Rekam suara dihentikan.',
            };

            return messages[error] || 'Voice-to-text gagal. Coba lagi.';
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

            activeUtterance = null;
            setSpeakButton(activeSpeakButton, false);
            activeSpeakButton = null;
        }

        function stopRecognition() {
            if (activeRecognition) {
                try {
                    activeRecognition.stop();
                } catch (error) {
                    try {
                        activeRecognition.abort();
                    } catch (abortError) {
                        // Browser can throw if recognition already ended.
                    }
                }
            }

            activeRecognition = null;
            setListenButton(activeListenButton, false);
            activeListenButton = null;
        }

        function stopVoiceTools() {
            stopSpeech();
            stopRecognition();
        }

        document.querySelectorAll('[data-voice-listen]').forEach((button) => {
            button.setAttribute('aria-pressed', 'false');

            if (!SpeechRecognitionApi) {
                button.disabled = true;
                button.title = 'Browser tidak mendukung voice-to-text';
                return;
            }

            button.addEventListener('click', () => {
                const input = voiceTarget(button, 'voiceListen');
                if (!input) return;

                if (activeListenButton === button) {
                    stopRecognition();
                    return;
                }

                stopVoiceTools();

                const recognition = new SpeechRecognitionApi();
                recognition.lang = 'id-ID';
                recognition.interimResults = false;
                recognition.continuous = false;
                recognition.onresult = (event) => {
                    const transcript = Array.from(event.results)
                        .map((result) => result[0]?.transcript || '')
                        .join(' ')
                        .trim();

                    if (transcript) {
                        appendVoiceText(input, transcript);
                        showVoiceToast('Voice note masuk ke catatan');
                    }
                };
                recognition.onerror = (event) => {
                    if (event.error !== 'aborted') {
                        showVoiceToast(getSpeechRecognitionErrorMessage(event.error));
                    }
                };
                recognition.onnomatch = () => {
                    showVoiceToast('Suara belum terbaca. Coba ulangi lebih jelas.');
                };
                recognition.onend = () => {
                    activeRecognition = null;
                    setListenButton(activeListenButton, false);
                    activeListenButton = null;
                };

                activeRecognition = recognition;
                activeListenButton = button;
                setListenButton(button, true);

                try {
                    recognition.start();
                } catch (error) {
                    stopRecognition();
                    showVoiceToast('Voice-to-text gagal dimulai. Pastikan halaman memakai HTTPS atau localhost.');
                }
            });
        });

        document.querySelectorAll('[data-voice-speak]').forEach((button) => {
            button.setAttribute('aria-pressed', 'false');

            if (!canSpeak) {
                button.disabled = true;
                button.title = 'Browser tidak mendukung text-to-voice';
                return;
            }

            button.addEventListener('click', () => {
                const input = voiceTarget(button, 'voiceSpeak');
                if (!input) return;

                if (activeSpeakButton === button || window.speechSynthesis.speaking) {
                    stopSpeech();
                    return;
                }

                const text = String(input.value || '').trim();
                if (!text) {
                    showVoiceToast('Isi catatan dulu');
                    return;
                }

                stopVoiceTools();

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                const voice = getIndonesianVoice();
                if (voice) utterance.voice = voice;
                utterance.rate = 0.95;
                utterance.pitch = 1;
                utterance.onend = () => {
                    activeUtterance = null;
                    setSpeakButton(activeSpeakButton, false);
                    activeSpeakButton = null;
                };
                utterance.onerror = () => {
                    activeUtterance = null;
                    setSpeakButton(activeSpeakButton, false);
                    activeSpeakButton = null;
                    showVoiceToast('Text-to-voice gagal dibaca');
                };

                activeUtterance = utterance;
                activeSpeakButton = button;
                setSpeakButton(button, true);
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            });
        });

        document.addEventListener('submit', stopVoiceTools);
        window.addEventListener('beforeunload', stopVoiceTools);
    </script>
</body>
</html>
