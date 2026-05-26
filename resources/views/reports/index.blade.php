<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; --warn: #ca8a04; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1180px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .cards, .content { display: grid; gap: 14px; }
        .topbar { grid-template-columns: minmax(0, 1fr) auto; align-items: center; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 16px 18px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06); }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
        .cards { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .content { grid-template-columns: 1fr 1fr; }
        .panel, .card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 18px; box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07); }
        h1, h2, p { margin: 0; }
        .muted { color: var(--muted); }
        .btn { min-height: 42px; border-radius: 8px; padding: 10px 12px; background: var(--primary); color: #fff; font-weight: 800; transition: background 160ms ease, transform 160ms ease; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; border: 0; cursor: pointer; font: inherit; }
        .card strong { display: block; margin-top: 8px; font-size: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 11px 8px; border-bottom: 1px solid var(--line); text-align: left; }
        th { color: var(--muted); font-size: 0.78rem; text-transform: uppercase; }
        tbody tr:hover { background: var(--soft); }
        .warn { color: var(--warn); font-weight: 800; }
        @media (max-width: 860px) { .topbar, .cards, .content { grid-template-columns: 1fr; } .table-wrap { overflow-x: auto; } }
    </style>
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Laporan</h1>
            </div>
            <div class="actions">
                <a class="btn" href="{{ route('dashboard.index') }}">Dashboard</a>
                <a class="btn" href="{{ route('reports.print') }}" target="_blank" rel="noopener">Print</a>
                <a class="btn" href="{{ route('reports.pdf') }}">PDF</a>
                <a class="btn" href="{{ route('reports.excel') }}">Excel</a>
                @if (auth()->user()->hasPermission('transactions.create') || auth()->user()->hasPermission('transactions.manage'))
                    <a class="btn" href="{{ route('pos.index') }}">Kasir</a>
                @endif
                @if (auth()->user()->hasPermission('products.manage') || auth()->user()->hasPermission('stock.manage') || auth()->user()->hasPermission('inventory.manage'))
                    <a class="btn" href="{{ route('products.index') }}">Produk</a>
                @endif
                @if (auth()->user()->hasPermission('transactions.manage') || auth()->user()->hasPermission('reports.view_store') || auth()->user()->hasPermission('reports.view_all') || auth()->user()->hasPermission('cashiers.monitor') || auth()->user()->hasPermission('dashboard.view') || auth()->user()->hasPermission('profits.view'))
                    <a class="btn" href="{{ route('sales.index') }}">Order</a>
                @endif
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            </div>
        </section>

        <section class="cards">
            <div class="card">
                <p class="muted">Penjualan bersih hari ini</p>
                <strong>Rp {{ number_format($todayRevenue, 0, ',', '.') }}</strong>
            </div>
            <div class="card">
                <p class="muted">Order hari ini</p>
                <strong>{{ $todayOrders }}</strong>
            </div>
            <div class="card">
                <p class="muted">Omzet bulan ini</p>
                <strong>Rp {{ number_format($monthRevenue, 0, ',', '.') }}</strong>
            </div>
            <div class="card">
                <p class="muted">PPN hari ini</p>
                <strong>Rp {{ number_format($todayFinancials['taxes'], 0, ',', '.') }}</strong>
            </div>
        </section>

        <section class="content">
            <div class="panel">
                <h2>Ringkasan Keuangan Hari Ini</h2>
                <div class="table-wrap">
                    <table>
                        <tbody>
                            <tr>
                                <td>Penjualan kotor</td>
                                <td>Rp {{ number_format($todayFinancials['grossSales'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Diskon</td>
                                <td>Rp {{ number_format($todayFinancials['discounts'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>PPN 11%</td>
                                <td>Rp {{ number_format($todayFinancials['taxes'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Penjualan bersih</strong></td>
                                <td><strong>Rp {{ number_format($todayFinancials['netSales'], 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <td>Uang diterima</td>
                                <td>Rp {{ number_format($todayFinancials['cashTendered'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Kembalian</td>
                                <td>Rp {{ number_format($todayFinancials['changeGiven'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Metode Pembayaran Hari Ini</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th>Order</th>
                                <th>Penjualan</th>
                                <th>Diterima</th>
                                <th>Kembali</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paymentSummary as $payment)
                                <tr>
                                    <td>{{ $payment->payment_method }}</td>
                                    <td>{{ $payment->orders_count }}</td>
                                    <td>Rp {{ number_format($payment->total_sales, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($payment->tendered, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($payment->change_given, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Belum ada pembayaran hari ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="panel">
                <h2>Produk Terlaris</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Terjual</th>
                                <th>Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topItems as $item)
                                <tr>
                                    <td>{{ $item->product_name }}<br><span class="muted">{{ $item->sku }}</span></td>
                                    <td>{{ $item->sold_qty }}</td>
                                    <td>Rp {{ number_format($item->revenue, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">Belum ada penjualan.</td></tr>
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
                                    <td>{{ $product->category?->name }}</td>
                                    <td class="warn">{{ $product->stock }} {{ $product->unit }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">Tidak ada stok menipis.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
