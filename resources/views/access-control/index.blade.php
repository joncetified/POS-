<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses User - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; --warn: #ca8a04; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input { font: inherit; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1220px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel, .access-card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        .btn:hover { transform: translateY(-1px); }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.55rem, 2.4vw, 2rem); }
        h2 { font-size: 1.1rem; }
        h3 { font-size: 1rem; }
        .muted { color: var(--muted); }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        .panel { padding: 18px; display: grid; gap: 12px; }
        .access-grid { display: grid; gap: 14px; }
        .access-card { padding: 16px; display: grid; gap: 14px; }
        .access-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .role-badge, .lock-badge { display: inline-flex; min-height: 28px; align-items: center; border-radius: 999px; padding: 4px 9px; font-size: .78rem; font-weight: 900; }
        .role-badge { background: #f8fafc; color: var(--ink); border: 1px solid var(--line); }
        .lock-badge { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .permission-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .permission-option { min-height: 86px; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 10px; align-items: flex-start; border: 1px solid var(--line); border-radius: 8px; padding: 12px; background: var(--soft); cursor: pointer; }
        .permission-option input { width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary); }
        .permission-option strong, .permission-option span { display: block; overflow-wrap: anywhere; }
        .permission-option span { margin-top: 3px; color: var(--muted); font-size: .88rem; line-height: 1.35; }
        .permission-option.locked { cursor: not-allowed; opacity: .74; }
        .card-actions { display: flex; justify-content: flex-end; }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; }
        @media (max-width: 860px) { .topbar, .permission-list { grid-template-columns: 1fr; } .actions, .card-actions { justify-content: flex-start; } .actions .btn, .actions button, .card-actions .btn { width: 100%; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Akses User</h1>
                <p class="muted">Checklist halaman yang boleh dibuka setiap user. Perubahan ini disimpan di database.</p>
            </div>
            <div class="actions">
                @if (auth()->user()->hasPermission('page.dashboard'))
                    <a class="btn" href="{{ route('dashboard.index') }}">Dashboard</a>
                @endif
                @if (auth()->user()->hasPermission('page.pos'))
                    <a class="btn primary" href="{{ route('pos.index') }}">Kasir</a>
                @endif
                @if (auth()->user()->hasPermission('page.products'))
                    <a class="btn" href="{{ route('products.index') }}">Produk</a>
                @endif
                @if (auth()->user()->hasPermission('page.reports'))
                    <a class="btn" href="{{ route('reports.index') }}">Laporan</a>
                @endif
                @if (auth()->user()->hasPermission('page.settings'))
                    <a class="btn" href="{{ route('settings.index') }}">Settings</a>
                @endif
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            </div>
        </section>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <div>
                <h2>Checklist Halaman</h2>
                <p class="muted">Centang berarti user boleh membuka halaman. Kosong berarti ditolak, walaupun role default biasanya punya akses.</p>
            </div>

            <div class="access-grid">
                @foreach ($users as $account)
                    @php($locked = $account->role === \App\Enums\UserRole::SuperAdmin)
                    <article class="access-card">
                        <div class="access-head">
                            <div>
                                <h3>{{ $account->name }}</h3>
                                <p class="muted">{{ $account->username }} · {{ $account->email }}</p>
                            </div>
                            <div>
                                <span class="role-badge">{{ $account->roleLabel() }}</span>
                                @if ($locked)
                                    <span class="lock-badge">Dikunci</span>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('access-control.update', $account) }}">
                            @csrf
                            @method('PATCH')
                            <div class="permission-list">
                                @foreach ($pages as $permission => $page)
                                    <label class="permission-option @if ($locked) locked @endif">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission }}"
                                            @checked($locked || $account->hasPermission($permission))
                                            @disabled($locked)
                                        >
                                        <span>
                                            <strong>{{ $page['label'] }}</strong>
                                            <span>{{ $page['description'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @if (! $locked)
                                <div class="card-actions" style="margin-top: 12px;">
                                    <button class="btn primary" type="submit">Simpan Akses</button>
                                </div>
                            @else
                                <p class="muted" style="margin-top: 10px;">Akses Super Admin selalu penuh supaya tidak ada akun utama yang terkunci.</p>
                            @endif
                        </form>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
