<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: var(--primary); font-weight: 800; text-decoration: none; }
        .auth-card { width: min(460px, 100%); display: grid; gap: 18px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 26px; box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07); }
        h1, p { margin: 0; }
        .muted { color: var(--muted); }
        form, .field { display: grid; gap: 12px; }
        .field { gap: 6px; }
        label { color: var(--muted); font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }
        input { width: 100%; min-height: 44px; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; color: var(--ink); background: var(--soft); font: inherit; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12); }
        .btn { min-height: 44px; border: 0; border-radius: 8px; padding: 10px 12px; color: #fff; background: var(--primary); cursor: pointer; font: inherit; font-weight: 900; transition: background 160ms ease, transform 160ms ease; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .errors { padding: 10px 12px; border-radius: 8px; color: #991b1b; background: #fee2e2; }
        .footer { display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-card">
        <div>
            <p class="muted">{{ $store['name'] }}</p>
            <h1>Register</h1>
        </div>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirmation">Konfirmasi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
            <button class="btn" type="submit">Buat Akun</button>
        </form>

        <div class="footer">
            <span class="muted">Sudah punya akun?</span>
            <a href="{{ route('login') }}">Login</a>
        </div>
    </main>
</body>
</html>
