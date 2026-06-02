<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        .shell { width: min(1220px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        h1, h2, p { margin: 0; }
        .muted { color: var(--muted); }
        .panel { padding: 18px; display: grid; gap: 14px; }
        .profile-form { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 26px; align-items: start; }
        .avatar-editor { display: grid; gap: 12px; align-content: start; width: 100%; max-width: 260px; }
        .avatar-canvas { width: 220px; height: 220px; max-width: 100%; border: 1px solid var(--line); border-radius: 28px; background: #fffaf3; object-fit: cover; }
        .avatar-editor input[type="file"] { width: 100%; max-width: 220px; min-height: 42px; padding: 8px; overflow: hidden; text-overflow: ellipsis; }
        .avatar-editor input[type="range"] { width: 220px; max-width: 100%; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .profile-fields { display: grid; gap: 16px; min-width: 0; }
        .field { display: grid; gap: 6px; }
        label { color: var(--muted); font-size: .76rem; font-weight: 850; text-transform: uppercase; }
        input { min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--surface); color: var(--ink); }
        .profile-actions { display: flex; justify-content: flex-start; padding-top: 2px; }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        .logout-form { margin: 0; }
        @media (max-width: 860px) { .topbar, .profile-form, .form-grid { grid-template-columns: 1fr; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
            </div>
            @include('partials.staff-nav')
        </section>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="panel page-heading">
            <div>
                <p class="muted">{{ $user->roleLabel() }}</p>
                <h1>Profil Saya</h1>
                <p class="muted">Ganti foto muka dan password akun login sendiri.</p>
            </div>
        </section>

        <section class="panel">
            <form class="profile-form" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <div class="avatar-editor" data-avatar-editor>
                    <canvas class="avatar-canvas" width="512" height="512" data-avatar-canvas></canvas>
                    <input type="file" accept="image/*" data-avatar-input aria-label="Foto profil saya">
                    <input type="range" min="1" max="3" step="0.05" value="1" data-avatar-zoom aria-label="Zoom crop foto">
                    <input type="hidden" name="avatar_crop" data-avatar-crop>
                    <span class="muted">Pilih foto muka, lalu atur zoom crop kotak.</span>
                    @if ($user->avatar_path)
                        <input type="hidden" data-avatar-current value="{{ asset('storage/' . $user->avatar_path) }}">
                    @endif
                </div>

                <div class="profile-fields">
                    <div class="form-grid">
                        <div class="field">
                            <label>Nama tampilan</label>
                            <input name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="field">
                            <label>Username</label>
                            <input value="{{ $user->username }}" disabled>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input value="{{ $user->email }}" disabled>
                        </div>
                        <div class="field">
                            <label>Password sekarang</label>
                            <input name="current_password" type="password" autocomplete="current-password" placeholder="Wajib jika ganti password">
                        </div>
                        <div class="field">
                            <label>Password baru</label>
                            <input name="password" type="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                        </div>
                        <div class="field">
                            <label>Konfirmasi password baru</label>
                            <input name="password_confirmation" type="password" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="profile-actions">
                        <button class="btn primary" type="submit">Simpan Profil Saya</button>
                    </div>
                </div>
            </form>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-avatar-editor]').forEach((editor) => {
            const input = editor.querySelector('[data-avatar-input]');
            const zoom = editor.querySelector('[data-avatar-zoom]');
            const canvas = editor.querySelector('[data-avatar-canvas]');
            const output = editor.querySelector('[data-avatar-crop]');
            const current = editor.querySelector('[data-avatar-current]')?.value;
            const context = canvas.getContext('2d');
            let image = new Image();
            let loaded = false;

            function draw() {
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.fillStyle = '#fff3ec';
                context.fillRect(0, 0, canvas.width, canvas.height);

                if (!loaded) {
                    context.fillStyle = '#ff965f';
                    context.font = '700 44px sans-serif';
                    context.textAlign = 'center';
                    context.fillText('Foto', canvas.width / 2, canvas.height / 2 + 12);
                    return;
                }

                const scale = Number(zoom.value || 1);
                const cover = Math.max(canvas.width / image.width, canvas.height / image.height) * scale;
                const width = image.width * cover;
                const height = image.height * cover;
                const x = (canvas.width - width) / 2;
                const y = (canvas.height - height) / 2;

                context.drawImage(image, x, y, width, height);
                if (input.files?.length) {
                    output.value = canvas.toDataURL('image/jpeg', 0.88);
                }
            }

            function load(src) {
                image = new Image();
                image.onload = () => {
                    loaded = true;
                    draw();
                };
                image.src = src;
            }

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = () => load(String(reader.result));
                reader.readAsDataURL(file);
            });

            zoom.addEventListener('input', draw);

            if (current) {
                load(current);
            } else {
                draw();
            }
        });
    </script>
</body>
</html>
