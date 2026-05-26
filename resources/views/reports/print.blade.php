<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Laporan - {{ $store['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; background: #f3f4f6; font-family: Arial, sans-serif; }
        .page { width: min(960px, calc(100% - 32px)); margin: 24px auto; padding: 28px; background: #fff; }
        .top { display: flex; justify-content: space-between; gap: 16px; align-items: start; border-bottom: 2px solid #111; padding-bottom: 14px; margin-bottom: 18px; }
        h1, h2, p { margin: 0; }
        h1 { font-size: 1.5rem; }
        h2 { margin-top: 22px; font-size: 1.05rem; }
        .muted { color: #555; }
        .actions { display: flex; gap: 8px; }
        .btn { min-height: 38px; border: 1px solid #111; border-radius: 4px; padding: 8px 12px; color: #111; background: #fff; cursor: pointer; text-decoration: none; font-weight: 700; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px; }
        .box { border: 1px solid #bbb; padding: 10px; }
        .box strong { display: block; margin-top: 4px; font-size: 1.15rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        @media print {
            body { background: #fff; }
            .page { width: 100%; margin: 0; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="top">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Laporan POS Cafe</h1>
                <p>{{ $generatedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }}</p>
            </div>
            <div class="actions">
                <button class="btn" type="button" onclick="window.print()">Print</button>
                <a class="btn" href="{{ route('reports.pdf') }}">PDF</a>
                <a class="btn" href="{{ route('reports.excel') }}">Excel</a>
            </div>
        </section>

        <section class="summary">
            <div class="box"><span>Penjualan Bersih</span><strong>Rp {{ number_format($todayFinancials['netSales'], 0, ',', '.') }}</strong></div>
            <div class="box"><span>Order Hari Ini</span><strong>{{ $todayOrders }}</strong></div>
            <div class="box"><span>PPN Hari Ini</span><strong>Rp {{ number_format($todayFinancials['taxes'], 0, ',', '.') }}</strong></div>
        </section>

        <h2>Ringkasan Keuangan Hari Ini</h2>
        <table>
            <tbody>
                <tr><td>Penjualan kotor</td><td class="right">Rp {{ number_format($todayFinancials['grossSales'], 0, ',', '.') }}</td></tr>
                <tr><td>Diskon</td><td class="right">Rp {{ number_format($todayFinancials['discounts'], 0, ',', '.') }}</td></tr>
                <tr><td>PPN 11%</td><td class="right">Rp {{ number_format($todayFinancials['taxes'], 0, ',', '.') }}</td></tr>
                <tr><td><strong>Penjualan bersih</strong></td><td class="right"><strong>Rp {{ number_format($todayFinancials['netSales'], 0, ',', '.') }}</strong></td></tr>
                <tr><td>Uang diterima</td><td class="right">Rp {{ number_format($todayFinancials['cashTendered'], 0, ',', '.') }}</td></tr>
                <tr><td>Kembalian</td><td class="right">Rp {{ number_format($todayFinancials['changeGiven'], 0, ',', '.') }}</td></tr>
            </tbody>
        </table>

        <h2>Metode Pembayaran</h2>
        <table>
            <thead><tr><th>Metode</th><th>Order</th><th>Penjualan</th><th>Diterima</th><th>Kembali</th></tr></thead>
            <tbody>
                @forelse ($paymentSummary as $payment)
                    <tr>
                        <td>{{ $payment->payment_method }}</td>
                        <td>{{ $payment->orders_count }}</td>
                        <td class="right">Rp {{ number_format($payment->total_sales, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($payment->tendered, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($payment->change_given, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada pembayaran hari ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Transaksi Hari Ini</h2>
        <table>
            <thead><tr><th>Invoice</th><th>Pelanggan</th><th>Meja</th><th>Metode</th><th>Total</th><th>Waktu</th></tr></thead>
            <tbody>
                @forelse ($todaySales as $sale)
                    <tr>
                        <td>{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->customer_name ?: 'Umum' }}</td>
                        <td>{{ $sale->table_number ?: '-' }}</td>
                        <td>{{ $sale->payment_method }}</td>
                        <td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                        <td>{{ $sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada transaksi hari ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
