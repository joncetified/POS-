<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Pesanan Meja - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f6f3ef; --surface: #fff; --ink: #24140c; --muted: #7c6b5c; --line: #e8ded3; --brown: #4b2308; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .shell { width: min(1120px, calc(100% - 28px)); margin: 22px auto; display: grid; gap: 16px; }
        .topbar, .card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(56, 28, 7, .07); }
        .topbar { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 18px; }
        h1, h2, p { margin: 0; }
        .muted { color: var(--muted); }
        .btn { min-height: 42px; border: 1px solid var(--brown); border-radius: 8px; padding: 9px 13px; color: #fff; background: var(--brown); cursor: pointer; font: inherit; font-weight: 850; text-decoration: none; display: inline-flex; align-items: center; }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .card { padding: 14px; display: grid; gap: 10px; text-align: center; }
        .card img { width: 180px; max-width: 100%; aspect-ratio: 1; justify-self: center; border: 1px solid var(--line); border-radius: 8px; }
        .link { color: var(--brown); overflow-wrap: anywhere; font-size: .85rem; }
        @media print {
            body { background: #fff; }
            .topbar .btn { display: none; }
            .shell { width: 100%; margin: 0; }
            .card { break-inside: avoid; box-shadow: none; }
        }
        @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 540px) { .topbar { align-items: flex-start; flex-direction: column; } .grid { grid-template-columns: 1fr; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>QR Pesanan Meja</h1>
                <p class="muted">Halaman staff untuk print QR. Pelanggan hanya membuka menu setelah scan QR yang ditempel di meja.</p>
            </div>
            <button class="btn" type="button" onclick="window.print()">Print QR</button>
        </section>

        <section class="grid">
            @foreach ($tables as $table)
                @php
                    $url = route('customer.table.menu', ['tableNumber' => $table]);
                    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($url);
                @endphp
                <article class="card">
                    <div>
                        <p class="muted">Meja</p>
                        <h2>{{ $table }}</h2>
                    </div>
                    <img src="{{ $qr }}" alt="QR pesanan meja {{ $table }}">
                    <a class="link" href="{{ $url }}">{{ $url }}</a>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
