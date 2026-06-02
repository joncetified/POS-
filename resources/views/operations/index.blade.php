<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operasional ERP - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .shell { width: min(1240px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel, .card { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
        .btn { min-height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); font-weight: 850; cursor: pointer; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.5rem, 2.4vw, 2rem); }
        h2 { font-size: 1.12rem; }
        .muted { color: var(--muted); }
        .cards { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .card { padding: 16px; display: grid; gap: 8px; min-height: 112px; }
        .card strong { font-size: 1.22rem; overflow-wrap: anywhere; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; align-items: start; }
        .panel { padding: 18px; display: grid; gap: 14px; }
        .form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; align-items: end; }
        .field { display: grid; gap: 6px; }
        label { color: var(--muted); font-size: .76rem; font-weight: 850; text-transform: uppercase; }
        input, select, textarea { width: 100%; min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--surface); color: var(--ink); }
        textarea { min-height: 42px; resize: vertical; }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: .76rem; text-transform: uppercase; }
        .table-wrap { overflow-x: auto; }
        .table-wrap table { min-width: 720px; }
        .logout-form { margin: 0; }
        @media (max-width: 1100px) { .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } .topbar, .grid, .form-grid { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } }
        @media (max-width: 560px) { .cards { grid-template-columns: 1fr; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
                <span class="staff-page-title">Operasional / ERP</span>
            </div>
            @include('partials.staff-nav')
            <div class="actions legacy-actions" hidden>
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

        <section class="cards">
            <article class="card"><p class="muted">Omzet bulan ini</p><strong>Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Biaya operasional</p><strong>Rp {{ number_format($summary['expenses'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Gaji terbayar</p><strong>Rp {{ number_format($summary['salaries'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Belanja stok</p><strong>Rp {{ number_format($summary['inventory_cost'], 0, ',', '.') }}</strong></article>
            <article class="card"><p class="muted">Estimasi profit</p><strong>Rp {{ number_format($summary['net'], 0, ',', '.') }}</strong></article>
        </section>

        <section class="grid">
            <div class="panel">
                <h2>Biaya Operasional</h2>
                <form class="form-grid" method="POST" action="{{ route('operations.expenses.store') }}">
                    @csrf
                    <div class="field"><label>Kategori</label><input name="category" placeholder="Listrik, sewa, bahan" required></div>
                    <div class="field"><label>Deskripsi</label><input name="description" required></div>
                    <div class="field"><label>Nominal</label><input name="amount" type="number" min="0" required></div>
                    <div class="field"><label>Tanggal</label><input name="spent_at" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label>Vendor</label><input name="vendor"></div>
                    <button class="btn primary" type="submit">Simpan Biaya</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th></tr></thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                <tr><td>{{ $expense->spent_at->format('d/m/Y') }}</td><td>{{ $expense->category }}</td><td>{{ $expense->description }}<br><span class="muted">{{ $expense->vendor ?: '-' }}</span></td><td>Rp {{ number_format($expense->amount, 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="muted">Belum ada biaya operasional.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <h2>Karyawan & Gaji</h2>
                <form class="form-grid" method="POST" action="{{ route('operations.employees.store') }}">
                    @csrf
                    <div class="field"><label>Nama</label><input name="name" required></div>
                    <div class="field"><label>Jabatan</label><input name="position" value="Barista" required></div>
                    <div class="field"><label>Telepon</label><input name="phone"></div>
                    <div class="field"><label>Gaji Pokok</label><input name="base_salary" type="number" min="0" required></div>
                    <input type="hidden" name="is_active" value="1">
                    <button class="btn primary" type="submit">Tambah Karyawan</button>
                </form>
                <form class="form-grid" method="POST" action="{{ route('operations.salaries.store') }}">
                    @csrf
                    <div class="field"><label>Karyawan</label><select name="employee_id" required>@foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->position }}</option>@endforeach</select></div>
                    <div class="field"><label>Periode</label><input name="period" type="month" value="{{ now()->format('Y-m') }}" required></div>
                    <div class="field"><label>Nominal</label><input name="amount" type="number" min="0" required></div>
                    <div class="field"><label>Tanggal Bayar</label><input name="paid_at" type="date" value="{{ now()->toDateString() }}" required></div>
                    <div class="field"><label>Catatan</label><input name="note"></div>
                    <button class="btn primary" type="submit" @disabled($employees->isEmpty())>Catat Gaji</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Karyawan</th><th>Periode</th><th>Nominal</th><th>Tanggal</th></tr></thead>
                        <tbody>
                            @forelse ($salaryPayments as $payment)
                                <tr><td>{{ $payment->employee?->name ?: '-' }}</td><td>{{ $payment->period }}</td><td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td><td>{{ $payment->paid_at->format('d/m/Y') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="muted">Belum ada pembayaran gaji.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Inventory Movement</h2>
            <form class="form-grid" method="POST" action="{{ route('operations.inventory.store') }}">
                @csrf
                <div class="field"><label>Produk</label><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} - stok {{ $product->stock }} {{ $product->unit }}</option>@endforeach</select></div>
                <div class="field"><label>Tipe</label><select name="type" required><option value="in">Stock In</option><option value="out">Stock Out</option><option value="adjustment">Set Stok Aktual</option></select></div>
                <div class="field"><label>Qty</label><input name="quantity" type="number" min="1" required></div>
                <div class="field"><label>Harga Modal / Unit</label><input name="unit_cost" type="number" min="0" value="0"></div>
                <div class="field"><label>Tanggal</label><input name="occurred_at" type="date" value="{{ now()->toDateString() }}" required></div>
                <div class="field"><label>Catatan</label><input name="note"></div>
                <button class="btn primary" type="submit" @disabled($products->isEmpty())>Simpan Movement</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tanggal</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Stok</th><th>Biaya</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->occurred_at->format('d/m/Y') }}</td>
                                <td>{{ $movement->product?->name ?: '-' }}</td>
                                <td>{{ strtoupper($movement->type) }}</td>
                                <td>{{ $movement->quantity }}</td>
                                <td>{{ $movement->stock_before }} -> {{ $movement->stock_after }}</td>
                                <td>Rp {{ number_format($movement->total_cost, 0, ',', '.') }}</td>
                                <td>{{ $movement->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
