<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; --warn: #ca8a04; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .shell { width: min(1220px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel, .card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        .btn:hover { transform: translateY(-1px); }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.5rem, 2.5vw, 2rem); }
        h2 { font-size: 1.12rem; }
        .muted { color: var(--muted); }
        .cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .card { padding: 16px; display: grid; gap: 8px; min-height: 116px; }
        .card strong { font-size: 1.45rem; overflow-wrap: anywhere; }
        .grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 16px; align-items: start; }
        .panel { padding: 18px; display: grid; gap: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: .78rem; text-transform: uppercase; }
        tbody tr:hover { background: var(--soft); }
        .status { display: inline-flex; min-height: 28px; align-items: center; border-radius: 999px; padding: 4px 9px; background: #fff7ed; color: #9a3412; font-weight: 850; }
        .good { color: var(--primary); font-weight: 850; }
        .warn { color: var(--warn); font-weight: 850; }
        .empty { min-height: 94px; display: grid; place-items: center; color: var(--muted); text-align: center; border: 1px dashed var(--line); border-radius: 8px; background: var(--soft); }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; }
        @media (max-width: 960px) { .topbar, .grid, .cards { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } .table-wrap { overflow-x: auto; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell dashboard-shell">
        <section class="topbar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
            </div>
            @include('partials.staff-nav')
            <div class="actions legacy-actions" hidden>
                @if (auth()->user()->hasPermission('page.pos'))
                    <a class="btn primary" href="{{ route('pos.index') }}">Kasir</a>
                @endif
                @if (auth()->user()->hasPermission('page.qr_tables'))
                    <a class="btn" href="{{ route('customer.qr.index') }}">QR Meja</a>
                @endif
                @if (auth()->user()->hasPermission('page.products'))
                    <a class="btn" href="{{ route('products.index') }}">Produk</a>
                @endif
                @if (auth()->user()->hasPermission('page.sales'))
                    <a class="btn" href="{{ route('sales.index') }}">Transaksi</a>
                @endif
                @if (auth()->user()->hasPermission('page.reports'))
                    <a class="btn" href="{{ route('reports.index') }}">Laporan</a>
                @endif
                @if (auth()->user()->hasPermission('page.operations'))
                    <a class="btn" href="{{ route('operations.index') }}">Operasional</a>
                @endif
                @if (auth()->user()->hasPermission('page.settings'))
                    <a class="btn" href="{{ route('settings.index') }}">Settings</a>
                @endif
                @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
                    <a class="btn" href="{{ route('access-control.index') }}">Akses User</a>
                @endif
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            </div>
        </section>

        <section class="cards">
            <article class="card">
                <p class="muted">Penjualan paid hari ini</p>
                <strong>Rp {{ number_format($todayRevenue, 0, ',', '.') }}</strong>
                <span class="good">{{ $todayOrders }} transaksi selesai</span>
            </article>
            <article class="card">
                <p class="muted">Open bill meja</p>
                <strong>{{ $openOrders->count() }}</strong>
                <span>Rp {{ number_format($openOrdersTotal, 0, ',', '.') }}</span>
            </article>
            <article class="card">
                <p class="muted">Rata-rata transaksi</p>
                <strong>Rp {{ number_format($averageOrder, 0, ',', '.') }}</strong>
                <span class="muted">Dari transaksi paid</span>
            </article>
            <article class="card">
                <p class="muted">Stok menipis</p>
                <strong>{{ $lowStockCount }}</strong>
                <span>{{ $activeProducts }} produk aktif</span>
            </article>
            <article class="card">
                <p class="muted">Estimasi profit hari ini</p>
                <strong>Rp {{ number_format($todayEstimatedProfit, 0, ',', '.') }}</strong>
                <span class="muted">Biaya Rp {{ number_format($todayOperationalCost + $todaySalaryCost + $todayInventoryCost, 0, ',', '.') }}</span>
            </article>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Open Bill Meja</h2>
                @if ($openOrders->isEmpty())
                    <div class="empty">Belum ada pesanan meja yang terbuka.</div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Meja</th>
                                    <th>Pelanggan</th>
                                    <th>Item</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($openOrders as $order)
                                    <tr>
                                        <td><strong>{{ $order->table_number ?: '-' }}</strong></td>
                                        <td>
                                            {{ $order->customer_name ?: 'Umum' }}
                                            @if ($order->customer_note)
                                                <br><span class="muted">Catatan: {{ $order->customer_note }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @foreach ($order->items as $item)
                                                {{ $item->product_name }} x {{ $item->quantity }}<br>
                                            @endforeach
                                        </td>
                                        <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                                        <td><span class="status">{{ $order->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="panel">
                <h2>Pembayaran Hari Ini</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th>Transaksi</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paymentSummary as $payment)
                                <tr>
                                    <td>{{ $payment->payment_method }}</td>
                                    <td>{{ $payment->orders_count }}</td>
                                    <td>Rp {{ number_format($payment->total_sales, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Transaksi Terakhir</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Meja</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestSales as $sale)
                                <tr>
                                    <td><strong>{{ $sale->invoice_number }}</strong></td>
                                    <td>{{ $sale->table_number ?: '-' }}</td>
                                    <td>{{ $sale->payment_method }}</td>
                                    <td>Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                    <td>{{ $sale->paid_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Belum ada transaksi paid.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Stok Menipis</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}<br><span class="muted">{{ $product->sku }}</span></td>
                                    <td>{{ $product->category?->name ?: '-' }}</td>
                                    <td class="warn">{{ $product->stock }} {{ $product->unit }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">Stok aman.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Produk Terlaris</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th>Terjual</th>
                            <th>Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topItems as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->sold_qty }}</td>
                                <td>Rp {{ number_format($item->revenue, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">Belum ada data produk terjual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
