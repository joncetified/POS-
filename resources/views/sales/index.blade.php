<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1180px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto auto auto auto; gap: 10px; align-items: center; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 16px 18px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06); }
        .panel { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 18px; box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07); }
        h1, p { margin: 0; }
        .muted { color: var(--muted); }
        .btn { min-height: 42px; border-radius: 8px; padding: 10px 12px; background: var(--primary); color: #fff; font-weight: 800; transition: background 160ms ease, transform 160ms ease; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; border: 0; cursor: pointer; font: inherit; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: 0.78rem; text-transform: uppercase; }
        tbody tr:hover { background: var(--soft); }
        .items { display: grid; gap: 4px; }
        .pagination { margin-top: 14px; }
        @media (max-width: 760px) { .topbar { grid-template-columns: 1fr; } .table-wrap { overflow-x: auto; } }
    </style>
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Order</h1>
            </div>
            <a class="btn" href="{{ route('pos.index') }}">Kasir</a>
            <a class="btn" href="{{ route('products.index') }}">Produk</a>
            <a class="btn" href="{{ route('reports.index') }}">Laporan</a>
            <form class="logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn" type="submit">Logout</button>
            </form>
        </section>

        <section class="panel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Pelanggan</th>
                            <th>Item</th>
                            <th>Pembayaran</th>
                            <th>Total</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td><strong>{{ $sale->invoice_number }}</strong></td>
                                <td>{{ $sale->customer_name ?: 'Umum' }}<br><span class="muted">{{ $sale->order_type }}</span></td>
                                <td>
                                    <div class="items">
                                        @foreach ($sale->items as $item)
                                            <span>{{ $item->product_name }} x {{ $item->quantity }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $sale->payment_method }}<br><span class="muted">Bayar Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span></td>
                                <td><strong>Rp {{ number_format($sale->total, 0, ',', '.') }}</strong></td>
                                <td>{{ $sale->paid_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $sales->links() }}</div>
        </section>
    </main>
</body>
</html>
