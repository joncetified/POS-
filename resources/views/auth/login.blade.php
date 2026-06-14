<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: var(--primary); font-weight: 800; text-decoration: none; }
        .auth-card { width: min(430px, 100%); display: grid; gap: 18px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 26px; box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07); }
        h1, p { margin: 0; }
        .muted { color: var(--muted); }
        form, .field { display: grid; gap: 12px; }
        .field { gap: 6px; }
        label { color: var(--muted); font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }
        input { width: 100%; min-height: 44px; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; color: var(--ink); background: var(--soft); font: inherit; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12); }
        .check { display: flex; gap: 8px; align-items: center; color: var(--muted); }
        .check input { width: auto; min-height: 0; }
        .btn { min-height: 44px; border: 0; border-radius: 8px; padding: 10px 12px; color: #fff; background: var(--primary); cursor: pointer; font: inherit; font-weight: 900; transition: background 160ms ease, transform 160ms ease; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .status { padding: 10px 12px; border-radius: 8px; color: #166534; background: #dcfce7; font-weight: 800; }
        .errors { padding: 10px 12px; border-radius: 8px; color: #991b1b; background: #fee2e2; }
        .footer { display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap; }
        .divider { height: 1px; background: var(--line); }
        .face-login { display: grid; gap: 12px; }
        .face-status { min-height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--soft); color: var(--muted); font-weight: 750; }
        .face-status.ok { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .face-status.warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .face-status.error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .face-video-wrap { min-height: 190px; border: 1px solid var(--line); border-radius: 8px; display: grid; place-items: center; overflow: hidden; background: #020617; }
        .face-video { width: 100%; max-height: 260px; aspect-ratio: 4 / 3; object-fit: cover; display: block; }
        .face-video:not([data-ready="1"]) { opacity: .2; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-card">
        <div>
            <p class="muted">{{ $store['name'] }}</p>
            <h1>Login</h1>
        </div>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <label class="check">
                <input name="remember" type="checkbox" value="1">
                Ingat saya
            </label>
            <button class="btn" type="submit">Masuk</button>
        </form>

        <div class="divider"></div>

        <section class="face-login" data-face-login>
            <div>
                <strong>Login dengan Face Recognition</strong>
                <p class="muted">Isi username, lalu kamera akan membandingkan wajah dengan data yang tersimpan di profil.</p>
            </div>
            <div class="face-video-wrap">
                <video class="face-video" data-face-video autoplay muted playsinline data-ready="0"></video>
            </div>
            <button class="btn" type="button" data-face-submit>Login Face Recognition</button>
            <div class="face-status" data-face-status>Face Recognition belum dimulai.</div>
        </section>

        <div class="footer">
            <span class="muted">Belum punya akun?</span>
            <a href="{{ route('register') }}">Register</a>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-face-login]').forEach((panel) => {
            const submitButton = panel.querySelector('[data-face-submit]');
            const status = panel.querySelector('[data-face-status]');
            const video = panel.querySelector('[data-face-video]');
            const username = document.querySelector('#username');
            const remember = document.querySelector('input[name="remember"]');

            function setStatus(message, type = '') {
                status.textContent = message;
                status.className = `face-status${type ? ` ${type}` : ''}`;
            }

            async function readJson(response, fallbackMessage) {
                const text = await response.text();
                try {
                    return text ? JSON.parse(text) : {};
                } catch (error) {
                    if (response.status === 419) {
                        throw new Error('Sesi login sudah kedaluwarsa. Refresh halaman lalu coba lagi.');
                    }

                    if (response.redirected || text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                        throw new Error(fallbackMessage);
                    }

                    throw new Error(text || fallbackMessage);
                }
            }

            submitButton.addEventListener('click', async () => {
                if (!username.value.trim()) {
                    username.focus();
                    setStatus('Isi username dulu sebelum login Face Recognition.', 'error');
                    return;
                }

                try {
                    submitButton.disabled = true;
                    setStatus('Meminta izin kamera...', 'warn');

                    const optionResponse = await fetch('{{ route('login.face.options') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ username: username.value.trim() }),
                    });
                    const optionPayload = await readJson(optionResponse, 'Server mengembalikan halaman, bukan JSON. Refresh halaman lalu coba lagi.');

                    if (!optionResponse.ok) {
                        throw new Error(optionPayload.message || 'Face Recognition belum bisa dimulai.');
                    }

                    await window.CafeFaceAuth.startCamera(video, (message) => setStatus(message, 'warn'));
                    setStatus('Membaca data wajah...', 'warn');
                    const descriptor = await window.CafeFaceAuth.captureStableDescriptor(video);
                    const response = await fetch('{{ route('login.face') }}', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            username: username.value.trim(),
                            remember: remember.checked,
                            face_descriptor: window.CafeFaceAuth.descriptorPayload(descriptor),
                        }),
                    });
                    const payload = await readJson(response, 'Server mengembalikan halaman, bukan JSON. Refresh halaman lalu coba lagi.');

                    if (!response.ok) {
                        throw new Error(payload.message || 'Login Face Recognition gagal.');
                    }

                    setStatus('Ini wajah pengguna terdaftar. Masuk...', 'ok');
                    window.location.href = payload.redirect;
                } catch (error) {
                    submitButton.disabled = false;
                    video.dataset.ready = '0';
                    setStatus(error.message || 'Login Face Recognition gagal.', 'error');
                } finally {
                    window.CafeFaceAuth.stopCamera(video);
                }
            });
        });
    </script>
</body>
</html>
