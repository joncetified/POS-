<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $store['name'] }} - POS Cafe</title>
    <style>
        :root {
            --bg: #f3f5f7;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --accent: #2563eb;
            --danger: #dc2626;
            --warning: #ca8a04;
            --success: #16a34a;
            --shadow: 0 18px 38px rgba(15, 23, 42, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: var(--bg);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        button {
            border: 0;
            cursor: pointer;
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 244px minmax(0, 1fr) 390px;
        }

        .sidebar,
        .cart-panel {
            background: var(--surface);
            border-color: var(--line);
        }

        .sidebar {
            border-right: 1px solid var(--line);
            padding: 22px 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .brand {
            display: flex;
            gap: 12px;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--line);
        }

        .brand-mark,
        .product-mark {
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: var(--primary);
            box-shadow: inset 0 -10px 18px rgba(15, 23, 42, 0.13);
        }

        .brand h1,
        .page-title h2,
        .panel-title h3,
        .product h3,
        p {
            margin: 0;
        }

        .brand h1 {
            font-size: 1.05rem;
        }

        .small {
            color: var(--muted);
            font-size: 0.82rem;
        }

        .nav {
            display: grid;
            gap: 8px;
        }

        .nav a,
        .logout-btn,
        .category-btn,
        .control-btn,
        .pay-btn,
        .quick-btn {
            border-radius: 8px;
            transition: 160ms ease;
        }

        .nav a,
        .logout-btn {
            width: 100%;
            padding: 11px 12px;
            color: var(--muted);
            background: transparent;
            text-align: left;
            font-weight: 700;
            text-decoration: none;
        }

        .nav a.active,
        .nav a:hover,
        .logout-btn:hover {
            color: var(--primary-dark);
            background: #e7f5f3;
        }

        .nav a.active {
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .logout-form {
            margin: 0;
        }

        .shift-card {
            margin-top: auto;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-soft);
            display: grid;
            gap: 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .main {
            min-width: 0;
            padding: 22px;
            display: grid;
            gap: 18px;
            align-content: start;
        }

        .topbar,
        .searchbar,
        .summary-grid,
        .category-row,
        .product-grid,
        .cart-header,
        .cart-item,
        .total-row,
        .payment-grid,
        .receipt-actions {
            display: grid;
            gap: 12px;
        }

        .topbar {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
        }

        .page-title h2 {
            font-size: clamp(1.5rem, 2vw, 2.15rem);
            line-height: 1.1;
        }

        .clock {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--muted);
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }

        .searchbar {
            grid-template-columns: minmax(0, 1fr) 170px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--ink);
            padding: 10px 12px;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }

        .summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .metric,
        .product,
        .cart-panel,
        .receipt {
            border: 1px solid var(--line);
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .metric {
            min-height: 94px;
            border-radius: 8px;
            padding: 15px;
            display: grid;
            align-content: space-between;
            transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .metric strong {
            font-size: 1.35rem;
        }

        .metric:hover,
        .product:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.09);
        }

        .category-row {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        }

        .category-btn {
            padding: 11px 10px;
            color: var(--muted);
            background: var(--surface);
            border: 1px solid var(--line);
            font-weight: 800;
        }

        .category-btn.active,
        .category-btn:hover {
            color: #fff;
            border-color: var(--primary);
            background: var(--primary);
        }

        .product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .product {
            min-height: 178px;
            border-radius: 8px;
            padding: 15px;
            display: grid;
            gap: 12px;
            align-content: space-between;
            transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
        }

        .product-top {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .product-mark {
            width: 46px;
            height: 46px;
            border-radius: 8px;
        }

        .product h3 {
            font-size: 1rem;
            line-height: 1.25;
        }

        .product-meta,
        .item-meta,
        .item-actions,
        .payment-option,
        .cart-footer-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .tag {
            width: max-content;
            padding: 5px 8px;
            border-radius: 999px;
            color: #334155;
            background: #e2e8f0;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .add-btn,
        .primary-btn,
        .ghost-btn,
        .danger-btn {
            min-height: 42px;
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 800;
        }

        .add-btn,
        .primary-btn {
            color: #fff;
            background: var(--primary);
        }

        .add-btn:hover,
        .primary-btn:hover {
            background: var(--primary-dark);
        }

        .add-btn,
        .primary-btn,
        .ghost-btn,
        .danger-btn,
        .control-btn,
        .pay-btn,
        .quick-btn {
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .add-btn:hover,
        .primary-btn:hover,
        .ghost-btn:hover,
        .danger-btn:hover,
        .control-btn:hover,
        .pay-btn:hover,
        .quick-btn:hover {
            transform: translateY(-1px);
        }

        .ghost-btn {
            color: var(--ink);
            background: var(--surface-soft);
            border: 1px solid var(--line);
        }

        .danger-btn {
            color: #fff;
            background: var(--danger);
        }

        .cart-panel {
            border-width: 0 0 0 1px;
            border-radius: 0;
            padding: 22px;
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr) auto;
            gap: 16px;
            min-height: 100vh;
            box-shadow: -16px 0 40px rgba(15, 23, 42, 0.05);
        }

        .cart-header {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--line);
        }

        .ticket-no {
            color: var(--primary-dark);
            font-weight: 900;
        }

        .order-type {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .control-btn,
        .pay-btn,
        .quick-btn {
            padding: 10px;
            color: var(--muted);
            background: var(--surface-soft);
            border: 1px solid var(--line);
            font-weight: 800;
        }

        .control-btn.active,
        .pay-btn.active,
        .quick-btn:hover {
            color: #fff;
            border-color: var(--primary);
            background: var(--primary);
        }

        .cart-list {
            min-height: 240px;
            overflow: auto;
            display: grid;
            gap: 10px;
            align-content: start;
            padding-right: 3px;
        }

        .cart-item {
            grid-template-columns: minmax(0, 1fr) auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px;
            background: var(--surface-soft);
        }

        table,
        input,
        select,
        button {
            letter-spacing: 0;
        }

        .qty-control {
            display: inline-grid;
            grid-template-columns: 30px 36px 30px;
            align-items: center;
            text-align: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            background: var(--surface);
        }

        .qty-control button {
            width: 30px;
            height: 30px;
            color: var(--ink);
            background: #eef2f7;
            font-weight: 900;
        }

        .empty-cart {
            min-height: 220px;
            display: grid;
            place-items: center;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--line);
            border-radius: 8px;
            background: var(--surface-soft);
            padding: 18px;
        }

        .totals {
            display: grid;
            gap: 8px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        .total-row {
            grid-template-columns: minmax(0, 1fr) auto;
            color: var(--muted);
        }

        .total-row strong {
            color: var(--ink);
        }

        .total-row.grand {
            align-items: center;
            color: var(--ink);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .payment-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .quick-cash {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .cart-footer-actions {
            margin-top: 4px;
        }

        .cart-footer-actions button {
            flex: 1;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 20;
            transform: translateY(16px);
            opacity: 0;
            pointer-events: none;
            transition: 180ms ease;
            padding: 12px 14px;
            border-radius: 8px;
            color: #fff;
            background: #0f172a;
            box-shadow: var(--shadow);
            font-weight: 800;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .modal {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: none;
            place-items: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.5);
        }

        .modal.open {
            display: grid;
        }

        .receipt {
            width: min(420px, 100%);
            border-radius: 8px;
            padding: 20px;
            display: grid;
            gap: 14px;
        }

        .receipt-line {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            border-bottom: 1px dashed var(--line);
            padding-bottom: 8px;
        }

        .receipt-actions {
            grid-template-columns: 1fr 1fr;
        }

        @media (max-width: 1180px) {
            .app-shell {
                grid-template-columns: 88px minmax(0, 1fr);
            }

            .sidebar {
                padding: 16px 10px;
            }

            .brand {
                justify-content: center;
            }

            .brand div:last-child,
            .nav span,
            .shift-card {
                display: none;
            }

            .nav a {
                text-align: center;
                padding: 12px 6px;
            }

            .logout-btn {
                text-align: center;
                padding: 12px 6px;
            }

            .cart-panel {
                grid-column: 1 / -1;
                min-height: auto;
                border-left: 0;
                border-top: 1px solid var(--line);
            }
        }

        @media (max-width: 860px) {
            .app-shell {
                display: block;
            }

            .sidebar {
                position: sticky;
                top: 0;
                z-index: 10;
                flex-direction: row;
                align-items: center;
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .brand {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .nav {
                grid-template-columns: repeat(4, auto);
                overflow: auto;
                flex: 1;
            }

            .main,
            .cart-panel {
                padding: 16px;
            }

            .topbar,
            .searchbar,
            .summary-grid,
            .category-row,
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .cart-header,
            .cart-item,
            .payment-grid,
            .quick-cash,
            .receipt-actions {
                grid-template-columns: 1fr;
            }

            .order-type {
                grid-template-columns: 1fr;
            }

            .item-actions,
            .cart-footer-actions {
                align-items: stretch;
                flex-direction: column;
            }
        }

        @media print {
            body > *:not(.modal) {
                display: none !important;
            }

            .modal {
                position: static;
                display: block;
                padding: 0;
                background: #fff;
            }

            .receipt {
                width: 100%;
                border: 0;
                box-shadow: none;
            }

            .receipt-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">{{ strtoupper(substr($store['name'], 0, 1)) }}</div>
                <div>
                    <h1>{{ $store['name'] }}</h1>
                    <p class="small">Cafe POS</p>
                </div>
            </div>

            <nav class="nav" aria-label="Menu utama">
                <a class="active" href="{{ route('pos.index') }}">Kasir <span></span></a>
                <a href="{{ route('products.index') }}">Produk <span></span></a>
                <a href="{{ route('sales.index') }}">Order <span></span></a>
                <a href="{{ route('reports.index') }}">Laporan <span></span></a>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">Logout <span></span></button>
                </form>
            </nav>

            <div class="shift-card">
                <div>
                    <p class="small">User</p>
                    <strong>{{ auth()->user()->name }}</strong>
                    <p class="small">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <div>
                    <p class="small">Shift aktif</p>
                    <strong>{{ $shift['cashier'] }}</strong>
                </div>
                <div>
                    <p class="small">Tanggal</p>
                    <strong>{{ now()->timezone('Asia/Jakarta')->format('d M Y') }}</strong>
                </div>
            </div>
        </aside>

        <main class="main">
            <section class="topbar">
                <div class="page-title">
                    <p class="small">{{ $store['address'] }}</p>
                    <h2>Kasir Cafe</h2>
                </div>
                <div id="clock" class="clock">{{ now()->timezone('Asia/Jakarta')->format('H:i') }}</div>
            </section>

            <section class="summary-grid" aria-label="Ringkasan shift">
                <div class="metric">
                    <p class="small">Penjualan hari ini</p>
                    <strong>Rp {{ number_format($shift['revenue'], 0, ',', '.') }}</strong>
                </div>
                <div class="metric">
                    <p class="small">Order selesai</p>
                    <strong>{{ $shift['orders'] }}</strong>
                </div>
                <div class="metric">
                    <p class="small">Rata-rata nota</p>
                    <strong>Rp {{ number_format($shift['average'], 0, ',', '.') }}</strong>
                </div>
            </section>

            <section class="searchbar">
                <div class="field">
                    <label for="search">Cari produk</label>
                    <input id="search" type="search" placeholder="SKU atau nama produk">
                </div>
                <div class="field">
                    <label for="sort">Urutkan</label>
                    <select id="sort">
                        <option value="name">Nama</option>
                        <option value="price-low">Harga terendah</option>
                        <option value="price-high">Harga tertinggi</option>
                        <option value="stock-low">Stok menipis</option>
                    </select>
                </div>
            </section>

            <section class="category-row" aria-label="Kategori produk">
                <button class="category-btn active" type="button" data-category="Semua">
                    Semua
                </button>
                @foreach ($categories as $category)
                    <button class="category-btn" type="button" data-category="{{ $category->name }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </section>

            <section id="product-grid" class="product-grid" aria-live="polite"></section>
        </main>

        <aside class="cart-panel">
            <header class="cart-header">
                <div>
                    <p class="small">Nota aktif</p>
                    <h2 class="ticket-no" id="ticket-number">TRX-0001</h2>
                </div>
                <button id="hold-order" class="ghost-btn" type="button">Tahan</button>
            </header>

            <section class="order-type" aria-label="Tipe order">
                <button class="control-btn active" type="button" data-order-type="Dine in">Dine in</button>
                <button class="control-btn" type="button" data-order-type="Take away">Take away</button>
                <button class="control-btn" type="button" data-order-type="Delivery">Delivery</button>
            </section>

            <section id="cart-list" class="cart-list" aria-live="polite"></section>

            <footer>
                <div class="field">
                    <label for="customer">Pelanggan</label>
                    <input id="customer" type="text" placeholder="Nama pelanggan">
                </div>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <strong id="subtotal">Rp 0</strong>
                    </div>
                    <div class="total-row">
                        <span>Diskon</span>
                        <strong id="discount-label">Rp 0</strong>
                    </div>
                    <div class="field">
                        <label for="discount">Diskon rupiah</label>
                        <input id="discount" type="number" min="0" step="1000" value="0">
                    </div>
                    <div class="total-row">
                        <span>PPN 11%</span>
                        <strong id="tax">Rp 0</strong>
                    </div>
                    <div class="total-row grand">
                        <span>Total</span>
                        <strong id="grand-total">Rp 0</strong>
                    </div>
                </div>

                <div class="payment-grid" style="margin-top: 14px;">
                    <button class="pay-btn active" type="button" data-payment="Tunai">Tunai</button>
                    <button class="pay-btn" type="button" data-payment="QRIS">QRIS</button>
                    <button class="pay-btn" type="button" data-payment="Kartu">Kartu</button>
                </div>

                <div class="field" style="margin-top: 12px;">
                    <label for="cash">Bayar</label>
                    <input id="cash" type="number" min="0" step="1000" value="0">
                </div>

                <div class="quick-cash" style="margin-top: 8px;">
                    <button class="quick-btn" type="button" data-cash="50000">50K</button>
                    <button class="quick-btn" type="button" data-cash="100000">100K</button>
                    <button class="quick-btn" type="button" data-cash="150000">150K</button>
                    <button class="quick-btn" type="button" data-cash="exact">Pas</button>
                </div>

                <div class="total-row" style="margin-top: 12px;">
                    <span>Kembali</span>
                    <strong id="change">Rp 0</strong>
                </div>

                <div class="cart-footer-actions">
                    <button id="clear-order" class="danger-btn" type="button">Bersihkan</button>
                    <button id="checkout" class="primary-btn" type="button">Bayar</button>
                </div>
            </footer>
        </aside>
    </div>

    <div id="toast" class="toast" role="status"></div>

    <div id="receipt-modal" class="modal" aria-hidden="true">
        <section class="receipt">
            <div>
                <p class="small">{{ $store['name'] }}</p>
                <h2 id="receipt-ticket">TRX-0001</h2>
            </div>
            <div id="receipt-body"></div>
            <div class="receipt-actions">
                <button id="close-receipt" class="ghost-btn" type="button">Tutup</button>
                <button id="print-receipt" class="primary-btn" type="button">Cetak</button>
            </div>
        </section>
    </div>

    <script>
        const products = @json($products);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const taxRate = 0.11;
        const state = {
            category: 'Semua',
            orderType: 'Dine in',
            payment: 'Tunai',
            cart: new Map(),
        };

        const rupiah = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
        const byId = (id) => document.getElementById(id);

        const nodes = {
            grid: byId('product-grid'),
            cart: byId('cart-list'),
            search: byId('search'),
            sort: byId('sort'),
            subtotal: byId('subtotal'),
            discount: byId('discount'),
            discountLabel: byId('discount-label'),
            tax: byId('tax'),
            grandTotal: byId('grand-total'),
            cash: byId('cash'),
            change: byId('change'),
            ticket: byId('ticket-number'),
            customer: byId('customer'),
            toast: byId('toast'),
            modal: byId('receipt-modal'),
            receiptTicket: byId('receipt-ticket'),
            receiptBody: byId('receipt-body'),
        };

        function initials(name) {
            return name.split(' ').slice(0, 2).map((word) => word[0]).join('').toUpperCase();
        }

        function totals() {
            const items = [...state.cart.values()];
            const subtotal = items.reduce((sum, item) => sum + item.price * item.qty, 0);
            const discount = Math.min(Number(nodes.discount.value || 0), subtotal);
            const taxable = Math.max(subtotal - discount, 0);
            const tax = taxable * taxRate;
            const total = taxable + tax;
            const cash = Number(nodes.cash.value || 0);

            return {
                items,
                subtotal,
                discount,
                tax,
                total,
                cash,
                change: Math.max(cash - total, 0),
            };
        }

        function filteredProducts() {
            const keyword = nodes.search.value.trim().toLowerCase();
            const filtered = products.filter((product) => {
                const matchesCategory = state.category === 'Semua' || product.category === state.category;
                const matchesKeyword = [product.sku, product.name, product.category]
                    .join(' ')
                    .toLowerCase()
                    .includes(keyword);

                return matchesCategory && matchesKeyword;
            });

            return filtered.sort((a, b) => {
                if (nodes.sort.value === 'price-low') return a.price - b.price;
                if (nodes.sort.value === 'price-high') return b.price - a.price;
                if (nodes.sort.value === 'stock-low') return a.stock - b.stock;
                return a.name.localeCompare(b.name);
            });
        }

        function renderProducts() {
            const list = filteredProducts();

            if (!list.length) {
                nodes.grid.innerHTML = '<div class="empty-cart">Produk tidak ditemukan.</div>';
                return;
            }

            nodes.grid.innerHTML = list.map((product) => {
                const inCart = state.cart.get(product.sku)?.qty || 0;
                const remaining = product.stock - inCart;
                const disabled = remaining <= 0 ? 'disabled' : '';

                return `
                    <article class="product">
                        <div class="product-top">
                            <div class="product-mark" style="background: ${product.color};">${initials(product.name)}</div>
                            <div>
                                <span class="tag">${product.tag}</span>
                                <h3 style="margin-top: 8px;">${product.name}</h3>
                                <p class="small">${product.sku} / ${product.category}</p>
                            </div>
                        </div>
                        <div class="product-meta">
                            <div>
                                <strong>${rupiah(product.price)}</strong>
                                <p class="small">Stok ${remaining} ${product.unit}</p>
                            </div>
                            <button class="add-btn" type="button" data-add="${product.sku}" ${disabled}>
                                ${remaining <= 0 ? 'Habis' : 'Tambah'}
                            </button>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function renderCart() {
            const data = totals();

            nodes.subtotal.textContent = rupiah(data.subtotal);
            nodes.discountLabel.textContent = rupiah(data.discount);
            nodes.tax.textContent = rupiah(data.tax);
            nodes.grandTotal.textContent = rupiah(data.total);
            nodes.change.textContent = rupiah(data.change);

            if (!data.items.length) {
                nodes.cart.innerHTML = '<div class="empty-cart">Keranjang kosong.</div>';
                renderProducts();
                return;
            }

            nodes.cart.innerHTML = data.items.map((item) => `
                <article class="cart-item">
                    <div>
                        <strong>${item.name}</strong>
                        <div class="item-meta">
                            <span class="small">${rupiah(item.price)} / ${item.unit}</span>
                            <span class="small">${rupiah(item.price * item.qty)}</span>
                        </div>
                    </div>
                    <div class="item-actions">
                        <div class="qty-control">
                            <button type="button" data-dec="${item.sku}">-</button>
                            <span>${item.qty}</span>
                            <button type="button" data-inc="${item.sku}">+</button>
                        </div>
                        <button class="ghost-btn" type="button" data-remove="${item.sku}">Hapus</button>
                    </div>
                </article>
            `).join('');

            renderProducts();
        }

        function addItem(sku) {
            const product = products.find((item) => item.sku === sku);
            const current = state.cart.get(sku) || { ...product, qty: 0 };

            if (!product || current.qty >= product.stock) {
                showToast('Stok tidak mencukupi');
                return;
            }

            current.qty += 1;
            state.cart.set(sku, current);
            renderCart();
        }

        function updateQty(sku, diff) {
            const item = state.cart.get(sku);

            if (!item) return;

            item.qty += diff;

            if (item.qty <= 0) {
                state.cart.delete(sku);
            } else {
                const product = products.find((entry) => entry.sku === sku);
                item.qty = Math.min(item.qty, product.stock);
                state.cart.set(sku, item);
            }

            renderCart();
        }

        function resetOrder(nextTicket = false) {
            state.cart.clear();
            nodes.discount.value = 0;
            nodes.cash.value = 0;
            nodes.customer.value = '';

            renderCart();
        }

        function showToast(message) {
            nodes.toast.textContent = message;
            nodes.toast.classList.add('show');
            window.setTimeout(() => nodes.toast.classList.remove('show'), 1800);
        }

        async function checkout() {
            const data = totals();

            if (!data.items.length) {
                showToast('Keranjang masih kosong');
                return;
            }

            if (state.payment === 'Tunai' && data.cash < data.total) {
                showToast('Nominal bayar kurang');
                return;
            }

            byId('checkout').disabled = true;
            byId('checkout').textContent = 'Menyimpan...';

            try {
                const response = await fetch('{{ route('sales.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        customer_name: nodes.customer.value || null,
                        cashier_name: '{{ $shift['cashier'] }}',
                        order_type: state.orderType,
                        payment_method: state.payment,
                        discount: data.discount,
                        paid_amount: state.payment === 'Tunai' ? data.cash : Math.round(data.total),
                        items: data.items.map((item) => ({
                            product_id: item.id,
                            quantity: item.qty,
                        })),
                    }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Transaksi gagal disimpan'];
                    showToast(errors[0]);
                    return;
                }

                showReceipt(payload.sale);
            } catch (error) {
                showToast('Koneksi ke server gagal');
            } finally {
                byId('checkout').disabled = false;
                byId('checkout').textContent = 'Bayar';
            }
        }

        function showReceipt(sale) {
            nodes.receiptTicket.textContent = sale.invoice_number;
            nodes.receiptBody.innerHTML = `
                <div class="receipt-line"><span>Waktu</span><strong>${sale.paid_at}</strong></div>
                <div class="receipt-line"><span>Tipe</span><strong>${sale.order_type}</strong></div>
                <div class="receipt-line"><span>Pelanggan</span><strong>${sale.customer_name || 'Umum'}</strong></div>
                ${sale.items.map((item) => `
                    <div class="receipt-line">
                        <span>${item.product_name} x ${item.quantity}</span>
                        <strong>${rupiah(item.line_total)}</strong>
                    </div>
                `).join('')}
                <div class="receipt-line"><span>Subtotal</span><strong>${rupiah(sale.subtotal)}</strong></div>
                <div class="receipt-line"><span>Diskon</span><strong>${rupiah(sale.discount)}</strong></div>
                <div class="receipt-line"><span>PPN</span><strong>${rupiah(sale.tax)}</strong></div>
                <div class="receipt-line"><span>Total</span><strong>${rupiah(sale.total)}</strong></div>
                <div class="receipt-line"><span>${sale.payment_method}</span><strong>${rupiah(sale.paid_amount)}</strong></div>
                <div class="receipt-line"><span>Kembali</span><strong>${rupiah(sale.change_amount)}</strong></div>
            `;
            nodes.modal.classList.add('open');
            nodes.modal.setAttribute('aria-hidden', 'false');
        }

        document.querySelectorAll('.category-btn').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.category-btn').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.category = button.dataset.category;
                renderProducts();
            });
        });

        document.querySelectorAll('[data-order-type]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-order-type]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.orderType = button.dataset.orderType;
            });
        });

        document.querySelectorAll('[data-payment]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-payment]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.payment = button.dataset.payment;

                if (state.payment !== 'Tunai') {
                    nodes.cash.value = Math.round(totals().total);
                }

                renderCart();
            });
        });

        nodes.grid.addEventListener('click', (event) => {
            const button = event.target.closest('[data-add]');
            if (button) addItem(button.dataset.add);
        });

        nodes.cart.addEventListener('click', (event) => {
            const dec = event.target.closest('[data-dec]');
            const inc = event.target.closest('[data-inc]');
            const remove = event.target.closest('[data-remove]');

            if (dec) updateQty(dec.dataset.dec, -1);
            if (inc) updateQty(inc.dataset.inc, 1);
            if (remove) {
                state.cart.delete(remove.dataset.remove);
                renderCart();
            }
        });

        document.querySelectorAll('[data-cash]').forEach((button) => {
            button.addEventListener('click', () => {
                const value = button.dataset.cash;
                nodes.cash.value = value === 'exact' ? Math.round(totals().total) : value;
                renderCart();
            });
        });

        nodes.search.addEventListener('input', renderProducts);
        nodes.sort.addEventListener('change', renderProducts);
        nodes.discount.addEventListener('input', renderCart);
        nodes.cash.addEventListener('input', renderCart);

        byId('clear-order').addEventListener('click', () => resetOrder(false));
        byId('hold-order').addEventListener('click', () => {
            if (!state.cart.size) {
                showToast('Tidak ada order untuk ditahan');
                return;
            }

            resetOrder(true);
            showToast('Order ditahan');
        });
        byId('checkout').addEventListener('click', checkout);
        byId('close-receipt').addEventListener('click', () => {
            nodes.modal.classList.remove('open');
            nodes.modal.setAttribute('aria-hidden', 'true');
            window.location.reload();
        });
        byId('print-receipt').addEventListener('click', () => window.print());

        window.setInterval(() => {
            byId('clock').textContent = new Intl.DateTimeFormat('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta',
            }).format(new Date());
        }, 1000);

        renderProducts();
        renderCart();
    </script>
</body>
</html>
