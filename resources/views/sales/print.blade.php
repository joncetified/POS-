<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Order - {{ $store['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; background: #f3f4f6; font-family: Arial, sans-serif; }
        .page { width: min(1120px, calc(100% - 32px)); margin: 24px auto; padding: 28px; background: #fff; }
        .top { display: flex; justify-content: space-between; gap: 16px; align-items: start; border-bottom: 2px solid #111; padding-bottom: 14px; margin-bottom: 18px; }
        h1, p { margin: 0; }
        h1 { font-size: 1.5rem; }
        .muted { color: #555; }
        .actions { display: flex; gap: 8px; }
        .btn { min-height: 38px; border: 1px solid #111; border-radius: 4px; padding: 8px 12px; color: #111; background: #fff; cursor: pointer; text-decoration: none; font-weight: 700; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
        .box { border: 1px solid #bbb; padding: 10px; }
        .box strong { display: block; margin-top: 4px; font-size: 1.05rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .items { display: grid; gap: 2px; }
        @media print {
            body { background: #fff; }
            .page { width: 100%; margin: 0; padding: 0; }
            .actions { display: none; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="page">
        <section class="top">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Order / Transaksi POS Cafe</h1>
                <p>{{ $generatedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }}</p>
            </div>
            <div class="actions">
                <button class="btn" type="button" onclick="window.print()">Print</button>
                <a class="btn" href="{{ route('sales.pdf') }}">PDF</a>
                <a class="btn" href="{{ route('sales.excel') }}">Excel</a>
            </div>
        </section>

        <section class="summary">
            <div class="box"><span>Total Order</span><strong>{{ $totals['orders'] }}</strong></div>
            <div class="box"><span>Total Penjualan</span><strong>Rp {{ number_format($totals['total'], 0, ',', '.') }}</strong></div>
            <div class="box"><span>Total Bayar</span><strong>Rp {{ number_format($totals['paid'], 0, ',', '.') }}</strong></div>
            <div class="box"><span>Total Kembali</span><strong>Rp {{ number_format($totals['change'], 0, ',', '.') }}</strong></div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Pelanggan</th>
                    <th>Item</th>
                    <th>Pembayaran</th>
                    <th>Subtotal</th>
                    <th>Diskon</th>
                    <th>PPN</th>
                    <th>Total</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $sale->invoice_number }}</td>
                        <td>
                            {{ $sale->customer_name ?: 'Umum' }}
                            <br><span class="muted">{{ $sale->order_type }}</span>
                            @if ($sale->table_number)
                                <br><span class="muted">Meja {{ $sale->table_number }}</span>
                            @endif
                            @if ($sale->customer_note)
                                <br><span class="muted">Catatan: {{ $sale->customer_note }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="items">
                                @foreach ($sale->items as $item)
                                    <span>{{ $item->product_name }} x {{ $item->quantity }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            {{ $sale->payment_method }}
                            @if ($sale->payment_reference)
                                <br><span class="muted">Ref {{ $sale->payment_reference }}</span>
                            @endif
                        </td>
                        <td class="right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($sale->discount, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($sale->tax, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td>
                        <td>{{ $sale->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
