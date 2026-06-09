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
        .fingerprint-login { display: grid; gap: 12px; }
        .fingerprint-status { min-height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--soft); color: var(--muted); font-weight: 750; }
        .fingerprint-status.ok { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .fingerprint-status.warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .fingerprint-status.error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
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

        <section class="fingerprint-login" data-fingerprint-login>
            <div>
                <strong>Login dengan Fingerprint</strong>
                <p class="muted">Isi username, lalu gunakan fingerprint/biometrik bawaan perangkat.</p>
            </div>
            <button class="btn" type="button" data-fingerprint-submit>Login Fingerprint</button>
            <div class="fingerprint-status" data-fingerprint-status>Fingerprint belum dimulai.</div>
        </section>

        <div class="footer">
            <span class="muted">Belum punya akun?</span>
            <a href="{{ route('register') }}">Register</a>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-fingerprint-login]').forEach((panel) => {
            const submitButton = panel.querySelector('[data-fingerprint-submit]');
            const status = panel.querySelector('[data-fingerprint-status]');
            const username = document.querySelector('#username');
            const remember = document.querySelector('input[name="remember"]');

            function setStatus(message, type = '') {
                status.textContent = message;
                status.className = `fingerprint-status${type ? ` ${type}` : ''}`;
            }

            submitButton.addEventListener('click', async () => {
                if (!username.value.trim()) {
                    username.focus();
                    setStatus('Isi username dulu sebelum login fingerprint.', 'error');
                    return;
                }

                try {
                    submitButton.disabled = true;
                    setStatus('Meminta fingerprint perangkat...', 'warn');

                    if (!await window.CafeFingerprintAuth.browserSupportsBiometric()) {
                        throw new Error('Perangkat/browser belum mendukung fingerprint atau biometrik.');
                    }

                    const optionResponse = await fetch('{{ route('login.fingerprint.options') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ username: username.value.trim() }),
                    });
                    const optionPayload = await optionResponse.json();

                    if (!optionResponse.ok) {
                        throw new Error(optionPayload.message || 'Fingerprint belum bisa dimulai.');
                    }

                    const credential = await navigator.credentials.get(
                        window.CafeFingerprintAuth.credentialRequestOptions(optionPayload.options)
                    );
                    const response = await fetch('{{ route('login.fingerprint') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            username: username.value.trim(),
                            remember: remember.checked,
                            ...window.CafeFingerprintAuth.authenticationPayload(credential),
                        }),
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Login fingerprint gagal.');
                    }

                    setStatus('Fingerprint cocok. Masuk...', 'ok');
                    window.location.href = payload.redirect;
                } catch (error) {
                    submitButton.disabled = false;
                    setStatus(error.message || 'Login fingerprint gagal.', 'error');
                }
            });
        });
    </script>
</body>
</html>
