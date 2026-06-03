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
        .period-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .period-actions .btn { background: var(--soft); color: var(--ink); border: 1px solid var(--line); }
        .period-actions .btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .chart-wrap { min-height: 300px; display: grid; gap: 14px; }
        .bar-chart { min-height: 240px; display: grid; grid-auto-flow: column; grid-auto-columns: minmax(34px, 1fr); gap: 8px; align-items: end; overflow-x: auto; padding: 12px 4px 2px; border: 1px solid var(--line); border-radius: 8px; background: #fffaf3; }
        .bar-item { min-width: 34px; display: grid; gap: 8px; align-items: end; justify-items: center; }
        .bar { width: 100%; min-height: 6px; border-radius: 8px 8px 2px 2px; background: linear-gradient(180deg, #ff965f, #0f766e); }
        .bar-label { color: var(--muted); font-size: .72rem; white-space: nowrap; writing-mode: vertical-rl; transform: rotate(180deg); }
        .chart-total { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .chart-total span { display: block; color: var(--muted); font-size: .8rem; }
        .chart-total strong { display: block; margin-top: 4px; font-size: 1.1rem; }
        @media (max-width: 860px) { .topbar, .cards, .content { grid-template-columns: 1fr; } .table-wrap { overflow-x: auto; } }
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

        <section class="panel page-heading">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Laporan</h1>
                <p class="muted">Ringkasan penjualan, pembayaran, stok, biaya operasional, dan chart periode.</p>
            </div>
            @if (auth()->user()->hasPermission('page.reports_export'))
                <div class="export-actions">
                    <a class="btn" href="{{ route('reports.print', ['period' => $selectedPeriod['key']]) }}" target="_blank" rel="noopener">Print</a>
                    <a class="btn" href="{{ route('reports.pdf', ['period' => $selectedPeriod['key']]) }}">PDF</a>
                    <a class="btn" href="{{ route('reports.excel', ['period' => $selectedPeriod['key']]) }}">Excel</a>
                </div>
            @endif
        </section>

        <section class="panel">
            <div>
                <h2>Periode Laporan</h2>
                <p class="muted">{{ $selectedPeriod['label'] }} / {{ $selectedPeriod['start']->format('d M Y H:i') }} - {{ $selectedPeriod['end']->format('d M Y H:i') }}</p>
            </div>
            <div class="period-actions">
                @foreach ($periodOptions as $option)
                    <a class="btn @class(['active' => $selectedPeriod['key'] === $option['key']])" href="{{ route('reports.index', ['period' => $option['key']]) }}">{{ $option['label'] }}</a>
                @endforeach
            </div>
        </section>

        <section class="cards">
            <div class="card">
                <p class="muted">Income {{ $selectedPeriod['label'] }}</p>
                <strong>Rp {{ number_format($periodFinancials['netSales'], 0, ',', '.') }}</strong>
            </div>
            <div class="card">
                <p class="muted">Order {{ $selectedPeriod['label'] }}</p>
                <strong>{{ $periodOrders }}</strong>
            </div>
            <div class="card">
                <p class="muted">Profit {{ $selectedPeriod['label'] }}</p>
                <strong>Rp {{ number_format($periodFinancials['estimatedProfit'], 0, ',', '.') }}</strong>
            </div>
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
            <div class="card">
                <p class="muted">Estimasi profit hari ini</p>
                <strong>Rp {{ number_format($todayFinancials['estimatedProfit'], 0, ',', '.') }}</strong>
            </div>
        </section>

        <section class="panel chart-wrap">
            <div>
                <h2>Diagram Income {{ $selectedPeriod['label'] }}</h2>
                <p class="muted">Chart omzet paid per {{ $selectedPeriod['granularity'] === 'month' ? 'bulan' : ($selectedPeriod['granularity'] === 'hour' ? 'jam' : 'hari') }}.</p>
            </div>
            <div class="chart-total">
                <div><span>Total income</span><strong>Rp {{ number_format($periodFinancials['netSales'], 0, ',', '.') }}</strong></div>
                <div><span>Total order</span><strong>{{ $periodOrders }}</strong></div>
                <div><span>PPN</span><strong>Rp {{ number_format($periodFinancials['taxes'], 0, ',', '.') }}</strong></div>
            </div>
            <div class="bar-chart" aria-label="Diagram income {{ $selectedPeriod['label'] }}">
                @foreach ($chartSeries as $point)
                    @php($height = max(6, (int) round(($point['total'] / $chartMax) * 100)))
                    <div class="bar-item" title="{{ $point['label'] }}: Rp {{ number_format($point['total'], 0, ',', '.') }}">
                        <div class="bar" style="height: {{ $height }}%;"></div>
                        <span class="bar-label">{{ $point['label'] }}</span>
                    </div>
                @endforeach
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
                            <tr>
                                <td>Biaya operasional</td>
                                <td>Rp {{ number_format($todayFinancials['operationalExpenses'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Gaji terbayar</td>
                                <td>Rp {{ number_format($todayFinancials['salaryPayments'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Belanja stok</td>
                                <td>Rp {{ number_format($todayFinancials['inventoryPurchases'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Estimasi profit</strong></td>
                                <td><strong>Rp {{ number_format($todayFinancials['estimatedProfit'], 0, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Metode Pembayaran {{ $selectedPeriod['label'] }}</h2>
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
                                <tr><td colspan="5" class="muted">Belum ada pembayaran di periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="panel">
                <h2>Transaksi {{ $selectedPeriod['label'] }}</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Metode</th>
                                <th>Total</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periodSales->take(10) as $sale)
                                <tr>
                                    <td><strong>{{ $sale->invoice_number }}</strong></td>
                                    <td>{{ $sale->customer_name ?: 'Umum' }}<br><span class="muted">{{ $sale->table_number ? 'Meja ' . $sale->table_number : $sale->order_type }}</span></td>
                                    <td>{{ $sale->payment_method }}</td>
                                    <td>Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                                    <td>{{ $sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Belum ada transaksi paid di periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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

        <section class="content">
            <div class="panel">
                <h2>Biaya Operasional Terakhir</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Nominal</th></tr></thead>
                        <tbody>
                            @forelse ($recentOperationalExpenses as $expense)
                                <tr><td>{{ $expense->spent_at->format('d/m/Y') }}</td><td>{{ $expense->category }}<br><span class="muted">{{ $expense->description }}</span></td><td>Rp {{ number_format($expense->amount, 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="muted">Belum ada biaya operasional.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Stock Movement Terakhir</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Produk</th><th>Tipe</th><th>Stok</th></tr></thead>
                        <tbody>
                            @forelse ($recentInventoryMovements as $movement)
                                <tr><td>{{ $movement->occurred_at->format('d/m/Y') }}</td><td>{{ $movement->product?->name ?: '-' }}</td><td>{{ strtoupper($movement->type) }}</td><td>{{ $movement->stock_before }} -> {{ $movement->stock_after }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="muted">Belum ada pergerakan stok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
