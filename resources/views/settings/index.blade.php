<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input, textarea { font: inherit; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1180px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel, .preview-card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        .btn:hover { transform: translateY(-1px); }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.55rem, 2.4vw, 2rem); }
        h2 { font-size: 1.12rem; }
        .muted { color: var(--muted); }
        .layout { display: grid; grid-template-columns: 1.2fr .8fr; gap: 16px; align-items: start; }
        .panel, .preview-card { padding: 18px; display: grid; gap: 14px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .field { display: grid; gap: 6px; }
        .wide { grid-column: 1 / -1; }
        label { color: var(--muted); font-size: .76rem; font-weight: 850; text-transform: uppercase; }
        input, textarea { width: 100%; min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--surface); color: var(--ink); }
        textarea { min-height: 96px; resize: vertical; line-height: 1.35; }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, .12); }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        .logo-preview { width: 96px; height: 96px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 8px; background: var(--soft); color: var(--primary); font-size: 2rem; font-weight: 950; overflow: hidden; }
        .logo-preview img { width: 100%; height: 100%; object-fit: cover; }
        .payment-barcode-preview { width: min(100%, 320px); min-height: 180px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 8px; background: var(--soft); color: var(--muted); overflow: hidden; }
        .payment-barcode-preview img { width: 100%; height: 100%; max-height: 240px; object-fit: contain; padding: 10px; background: #fff; }
        .identity { display: flex; gap: 12px; align-items: center; min-width: 0; }
        .identity h3, .identity p { overflow-wrap: anywhere; }
        .contact-list { display: grid; gap: 9px; }
        .contact-list div { border: 1px solid var(--line); border-radius: 8px; padding: 10px; background: var(--soft); overflow-wrap: anywhere; }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; }
        @media (max-width: 860px) { .topbar, .layout, .form-grid { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } .actions .btn, .actions button { width: 100%; } }
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

        <section class="layout">
            <div class="panel">
                <h2>Data Website</h2>
                <form class="form-grid" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="field wide">
                        <label for="company_name">Company name</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name', $settings->company_name) }}" required maxlength="160">
                    </div>

                    <div class="field">
                        <label for="manager_name">Manager</label>
                        <input id="manager_name" name="manager_name" value="{{ old('manager_name', $settings->manager_name) }}" maxlength="120">
                    </div>

                    <div class="field">
                        <label for="contact_phone">Phone</label>
                        <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" maxlength="80">
                    </div>

                    <div class="field">
                        <label for="contact_whatsapp">WhatsApp</label>
                        <input id="contact_whatsapp" name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" maxlength="80">
                    </div>

                    <div class="field">
                        <label for="contact_email">Email</label>
                        <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings->contact_email) }}" maxlength="160">
                    </div>

                    <div class="field wide">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" maxlength="500">{{ old('address', $settings->address) }}</textarea>
                    </div>

                    <div class="field wide">
                        <label for="logo">Logo</label>
                        <input id="logo" name="logo" type="file" accept="image/*">
                    </div>

                    <div class="field wide">
                        <label for="payment_barcode">Barcode Pembayaran</label>
                        <input id="payment_barcode" name="payment_barcode" type="file" accept="image/*">
                    </div>

                    <div class="wide">
                        <button class="btn primary" type="submit">Simpan Settings</button>
                    </div>
                </form>
            </div>

            <aside class="preview-card">
                <h2>Preview</h2>
                <div class="identity">
                    <div class="logo-preview">
                        @if ($store['logo_url'])
                            <img src="{{ $store['logo_url'] }}" alt="{{ $store['name'] }} logo">
                        @else
                            {{ strtoupper(substr($store['name'], 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <h3>{{ $store['name'] }}</h3>
                        <p class="muted">{{ $store['address'] }}</p>
                    </div>
                </div>

                <div class="contact-list">
                    <div>
                        <p class="muted">Barcode Pembayaran</p>
                        <div class="payment-barcode-preview">
                            @if ($store['payment_barcode_url'])
                                <img src="{{ $store['payment_barcode_url'] }}" alt="Barcode pembayaran">
                            @else
                                Belum ada barcode pembayaran.
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="muted">Manager</p>
                        <strong>{{ $store['manager'] ?: '-' }}</strong>
                    </div>
                    <div>
                        <p class="muted">Phone</p>
                        <strong>{{ $store['contact_phone'] ?: '-' }}</strong>
                    </div>
                    <div>
                        <p class="muted">WhatsApp</p>
                        <strong>{{ $store['contact_whatsapp'] ?: '-' }}</strong>
                    </div>
                    <div>
                        <p class="muted">Email</p>
                        <strong>{{ $store['contact_email'] ?: '-' }}</strong>
                    </div>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
