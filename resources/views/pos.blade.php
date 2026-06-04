<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $store['name'] }} - POS Cafe</title>
    <style>
        :root {
            --bg: #f6f3ef;
            --surface: #fff;
            --soft: #fbfaf8;
            --line: #e8ded3;
            --ink: #24140c;
            --muted: #7c6b5c;
            --brown: #4b2308;
            --brown-2: #6f390f;
            --accent: #f5b946;
            --danger: #dc2626;
            --success: #16a34a;
            --shadow: 0 16px 32px rgba(56, 28, 7, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: var(--bg);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; letter-spacing: 0; }
        button { border: 0; cursor: pointer; }
        h1, h2, h3, p { margin: 0; }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        .sidebar {
            display: none;
        }

        .receipt-logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .small, .muted { color: var(--muted); }
        .small { font-size: 0.82rem; }

        .logout-form { margin: 0; }

        .workspace {
            min-width: 0;
            display: grid;
            grid-template-rows: minmax(0, 1fr);
        }

        .content {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(390px, 39%);
            gap: 16px;
            padding: 18px;
        }

        .products-panel,
        .cart-panel {
            min-width: 0;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: var(--shadow);
        }

        .products-panel {
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            gap: 16px;
            padding: 16px;
        }

        .panel-head {
            display: grid;
            grid-template-columns: minmax(170px, 1fr) minmax(220px, 26%) minmax(260px, 32%) auto;
            gap: 16px;
            align-items: center;
        }

        .panel-head h2,
        .cart-title h2 {
            font-size: 1.28rem;
            line-height: 1.15;
        }

        .searchbox {
            min-height: 48px;
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 0 14px;
            background: #fff;
        }

        .searchbox span:last-child {
            display: none;
        }

        .searchbox input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--ink);
        }

        .scan-panel {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 96px;
            gap: 10px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px;
            background: #fffaf2;
        }

        .scan-panel strong {
            display: block;
            margin-bottom: 6px;
        }

        .scan-panel .searchbox {
            min-height: 40px;
            padding-inline: 10px;
        }

        .scan-preview {
            min-width: 0;
            display: grid;
            gap: 6px;
        }

        .scan-status {
            min-height: 18px;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .scan-product {
            min-width: 0;
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
        }

        .scan-thumb {
            width: 44px;
            height: 38px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 6px;
            color: #fff;
            background: var(--brown);
            font-size: .7rem;
            font-weight: 950;
        }

        .scan-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .scan-product h3 {
            margin: 0;
            font-size: .88rem;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .scan-barcode {
            width: 96px;
            min-height: 58px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            color: var(--muted);
            font-size: .72rem;
            text-align: center;
            overflow: hidden;
        }

        .scan-barcode img {
            width: 100%;
            height: 58px;
            object-fit: contain;
            display: block;
            padding: 4px;
        }

        .category-menu,
        .chip,
        .clear-btn,
        .save-btn,
        .park-btn,
        .checkout-btn {
            min-height: 48px;
            border-radius: 8px;
            font-weight: 850;
            transition: transform 160ms ease, background 160ms ease, border-color 160ms ease;
        }

        .category-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 18px;
            color: #fff;
            background: var(--brown);
        }

        .chips {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
        }

        .chip {
            flex: 0 0 auto;
            min-width: 96px;
            max-width: 148px;
            padding: 0 16px;
            border: 1px solid var(--line);
            color: #4b3524;
            background: #fff;
            line-height: 1.15;
        }

        .chip.active,
        .chip:hover {
            color: #fff;
            border-color: var(--brown);
            background: var(--brown);
        }

        .product-grid {
            min-height: 0;
            overflow: auto;
            display: grid;
            grid-template-columns: repeat(4, minmax(142px, 1fr));
            gap: 16px;
            padding-right: 2px;
            align-content: start;
        }

        .product-card {
            min-width: 0;
            min-height: 230px;
            border: 1px solid var(--line);
            border-radius: 7px;
            overflow: hidden;
            display: grid;
            grid-template-rows: 132px minmax(0, 1fr);
            background: #fff;
            transition: 160ms ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 28px rgba(56, 28, 7, 0.12);
        }

        .product-visual {
            position: relative;
            display: grid;
            place-items: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 30% 25%, rgba(255,255,255,.85), transparent 22%),
                linear-gradient(145deg, var(--tile-color), #f0d0a2);
        }

        .product-visual::before {
            content: "";
            width: 92px;
            height: 62px;
            border-radius: 999px 999px 42px 42px;
            background:
                radial-gradient(circle at 34% 26%, rgba(255,255,255,.62), transparent 18%),
                linear-gradient(145deg, rgba(255,255,255,.15), rgba(60,25,5,.26));
            box-shadow: 0 12px 18px rgba(59, 26, 8, 0.24);
        }

        .product-visual span {
            position: absolute;
            inset: auto 10px 10px auto;
            min-width: 42px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            color: #fff;
            background: rgba(48, 21, 5, 0.78);
            font-size: 0.78rem;
            font-weight: 950;
        }

        .product-barcode {
            position: absolute;
            inset: 10px 10px auto auto;
            width: 70px;
            height: 30px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.8);
            background: #fff;
        }

        .product-barcode img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .product-info {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 38px;
            gap: 8px;
            padding: 12px 10px 10px;
            align-items: end;
        }

        .product-info h3 {
            font-size: 0.96rem;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .price {
            margin-top: 6px;
            display: block;
            font-weight: 900;
        }

        .add-btn {
            width: 36px;
            height: 36px;
            border-radius: 7px;
            color: #fff;
            background: var(--brown);
            font-size: 1.4rem;
            line-height: 1;
        }

        .add-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .cart-panel {
            min-height: 0;
            display: grid;
            grid-template-rows: auto minmax(120px, 0.8fr) minmax(0, 1.2fr);
            padding: 16px;
            overflow: hidden;
        }

        .cart-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--line);
        }

        .cart-title {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .badge {
            min-width: 28px;
            height: 28px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--danger);
            font-size: 0.8rem;
            font-weight: 900;
        }

        .clear-btn {
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid var(--line);
            color: var(--ink);
            background: #fff;
            white-space: nowrap;
        }

        .cart-table {
            min-height: 0;
            overflow: auto;
        }

        .cart-row,
        .cart-header-row {
            display: grid;
            grid-template-columns: minmax(170px, 1.25fr) 100px 112px 118px 58px;
            gap: 12px;
            align-items: center;
            min-height: 68px;
            border-bottom: 1px solid var(--line);
        }

        .cart-header-row {
            min-height: 46px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .cart-product {
            min-width: 0;
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
        }

        .cart-thumb {
            width: 58px;
            height: 44px;
            border-radius: 6px;
            overflow: hidden;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(145deg, var(--tile-color), #e8c38f);
            font-size: 0.75rem;
            font-weight: 950;
        }

        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .qty-control {
            display: grid;
            grid-template-columns: 34px 36px 34px;
            min-height: 36px;
            border: 1px solid var(--line);
            border-radius: 7px;
            overflow: hidden;
            background: #fff;
            text-align: center;
            align-items: center;
        }

        .qty-control button {
            height: 36px;
            color: var(--brown);
            background: #fff;
            font-weight: 900;
        }

        .remove-btn {
            color: var(--danger);
            background: transparent;
            font-size: 0.82rem;
            font-weight: 900;
        }

        .empty {
            min-height: 220px;
            display: grid;
            place-items: center;
            color: var(--muted);
            text-align: center;
        }

        .checkout-area {
            min-height: 0;
            display: grid;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 4px;
        }

        .saved-orders {
            display: grid;
            gap: 8px;
            max-height: 146px;
            overflow: auto;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fffaf2;
        }

        .saved-order {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(82px, auto) minmax(82px, auto);
            gap: 8px;
            align-items: center;
            min-height: 38px;
        }

        .saved-order strong {
            overflow-wrap: anywhere;
            line-height: 1.25;
        }

        .saved-order button {
            min-height: 34px;
            border-radius: 6px;
            padding: 0 10px;
            color: var(--brown);
            background: #fff;
            border: 1px solid var(--line);
            font-weight: 800;
        }

        .customer-row,
        .discount-row,
        .pay-row,
        .action-row,
        .total-row {
            display: grid;
            gap: 10px;
            align-items: center;
        }

        .discount-row {
            grid-template-columns: auto 1fr 1fr;
        }

        .customer-row {
            grid-template-columns: minmax(0, 1fr) 150px;
        }

        .icon-cell {
            width: 36px;
            height: 36px;
            border: 1px solid var(--line);
            border-radius: 7px;
            display: grid;
            place-items: center;
            color: var(--brown);
            background: #fff;
            font-weight: 900;
        }

        .money-input {
            min-height: 40px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
        }

        .money-input span {
            padding: 0 12px;
            color: var(--muted);
        }

        .money-input input,
        .discount-row input,
        .customer-input {
            width: 100%;
            min-height: 40px;
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 0 12px;
            color: var(--ink);
            background: #fff;
            outline: 0;
        }

        .money-input input {
            border: 0;
            text-align: right;
        }

        .total-row {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .total-row.grand {
            font-size: 1.22rem;
            font-weight: 950;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .pay-btn {
            min-height: 66px;
            border: 1px solid var(--line);
            border-radius: 7px;
            display: grid;
            gap: 4px;
            place-items: center;
            color: #3f2a1c;
            background: #fff;
            font-weight: 800;
        }

        .pay-btn.active {
            border-color: var(--brown);
            background: #fff8ed;
            box-shadow: inset 0 0 0 1px var(--brown);
        }

        .pay-row {
            grid-template-columns: auto minmax(0, 170px) minmax(0, 1fr) auto;
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 10px;
            background: #fff;
        }

        .payment-status {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(160px, 220px);
            gap: 10px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 10px;
            background: #fffaf2;
        }

        .payment-status strong {
            display: block;
            margin-bottom: 2px;
        }

        .payment-status input {
            width: 100%;
            min-height: 38px;
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 0 10px;
            background: #fff;
            outline: 0;
        }

        .money-input input:read-only {
            color: var(--muted);
            background: #fbfaf8;
            cursor: not-allowed;
        }

        .change {
            color: var(--success);
            font-size: 1.15rem;
            font-weight: 950;
        }

        .action-row {
            grid-template-columns: 125px 125px minmax(0, 1fr);
        }

        .save-btn,
        .park-btn {
            border: 1px solid #f0c067;
            color: var(--brown);
            background: #fff8ed;
        }

        .park-btn {
            background: #ffc94b;
            border-color: #ffc94b;
        }

        .checkout-btn {
            min-height: 64px;
            color: #fff;
            background: linear-gradient(90deg, var(--brown), #6d350d);
            font-size: 1.05rem;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 44px;
            z-index: 20;
            transform: translateY(16px);
            opacity: 0;
            pointer-events: none;
            transition: 180ms ease;
            padding: 12px 14px;
            border-radius: 8px;
            color: #fff;
            background: #24140c;
            box-shadow: var(--shadow);
            font-weight: 850;
        }

        .toast.show { transform: translateY(0); opacity: 1; }

        .modal {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: none;
            place-items: center;
            padding: 18px;
            background: rgba(36, 20, 12, 0.55);
        }

        .modal.open { display: grid; }

        .receipt {
            width: min(360px, 100%);
            border-radius: 8px;
            padding: 18px;
            display: grid;
            gap: 12px;
            background: #fff;
            box-shadow: var(--shadow);
            font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace;
            font-size: 0.82rem;
            color: #111;
        }

        .receipt-header {
            text-align: center;
            display: grid;
            gap: 4px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #aaa;
        }

        .receipt-header h2 {
            font-size: 1.05rem;
            letter-spacing: 0;
        }

        .receipt-header strong {
            font-size: 1rem;
        }

        .receipt-section {
            display: grid;
            gap: 6px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #aaa;
        }

        .receipt-line {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            align-items: baseline;
        }

        .receipt-line span:first-child {
            color: #333;
        }

        .receipt-line strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .receipt-item {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
        }

        .receipt-thumb {
            width: 46px;
            height: 38px;
            border-radius: 5px;
            overflow: hidden;
            display: grid;
            place-items: center;
            color: #fff;
            background: #4b2308;
            font-size: 0.7rem;
            font-weight: 950;
        }

        .receipt-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .receipt-item-main {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .receipt-item-sub {
            color: #555;
            font-size: 0.76rem;
        }

        .receipt-total {
            font-size: 1rem;
            font-weight: 950;
        }

        .receipt-footer {
            text-align: center;
            color: #444;
            font-size: 0.78rem;
        }

        .receipt-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .qris-panel {
            width: min(380px, 100%);
            border-radius: 8px;
            padding: 18px;
            display: grid;
            gap: 14px;
            background: #fff;
            color: var(--ink);
            box-shadow: var(--shadow);
        }

        .qris-panel h2 {
            font-size: 1.1rem;
        }

        .qris-box {
            min-height: 260px;
            border: 1px solid var(--line);
            border-radius: 8px;
            display: grid;
            place-items: center;
            padding: 14px;
            background: #fffaf2;
            text-align: center;
        }

        .qris-box img {
            width: min(240px, 100%);
            aspect-ratio: 1;
            object-fit: contain;
            background: #fff;
            border-radius: 6px;
        }

        .qris-code {
            max-height: 140px;
            overflow: auto;
            overflow-wrap: anywhere;
            color: var(--muted);
            font-size: 0.76rem;
        }

        .qris-status {
            min-height: 38px;
            border-radius: 7px;
            display: grid;
            place-items: center;
            color: var(--brown);
            background: #fff8ed;
            font-weight: 850;
            text-align: center;
        }

        .barcode-confirm-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
            gap: 10px;
        }

        @media (max-width: 1320px) {
            .content { grid-template-columns: minmax(0, 1fr); }
            .cart-panel { min-height: 760px; }
        }

        @media (max-width: 940px) {
            .app-shell { display: block; }
            .sidebar {
                min-height: auto;
                position: sticky;
                top: 0;
                z-index: 12;
                grid-template-columns: auto 1fr auto;
                grid-template-rows: none;
                align-items: center;
                padding: 10px;
            }
            .workspace { min-height: 100vh; }
            .content { padding: 12px; }
            .panel-head { grid-template-columns: 1fr; }
            .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cart-row, .cart-header-row { grid-template-columns: minmax(150px, 1fr) 86px 104px 100px 58px; }
            .payment-grid { grid-template-columns: minmax(0, 1fr); }
            .pay-row, .action-row, .customer-row, .discount-row, .payment-status { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .product-grid { grid-template-columns: 1fr; }
            .cart-table { overflow-x: auto; }
            .cart-row, .cart-header-row { min-width: 620px; }
        }

        @media print {
            @page { size: 80mm auto; margin: 4mm; }
            html, body { width: 80mm; min-height: 0; background: #fff; }
            body > *:not(#receipt-modal) { display: none !important; }
            #qris-modal { display: none !important; }
            #receipt-modal {
                position: static;
                display: block !important;
                width: 80mm;
                padding: 0;
                background: #fff;
            }
            .receipt {
                width: 72mm;
                margin: 0 auto;
                border: 0;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }
            .receipt-actions { display: none; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
            </div>
            @include('partials.staff-nav')
        </aside>

        <main class="workspace">
            <section class="content">
                <section class="products-panel" aria-label="Daftar produk">
                    <div class="panel-head">
                        <div>
                            <p class="small">{{ $store['address'] }}</p>
                            <h2>Semua Produk</h2>
                        </div>
                        <label class="searchbox" for="search">
                            <span aria-hidden="true">#</span>
                            <input id="search" type="search" placeholder="Cari produk...">
                            <span>#</span>
                        </label>
                        <div class="scan-panel" aria-label="Scan barcode">
                            <div class="scan-preview">
                                    <strong>Scan Produk</strong>
                                <label class="searchbox" for="barcode-scan">
                                    <span aria-hidden="true">|</span>
                                    <input id="barcode-scan" type="search" inputmode="numeric" autocomplete="off" placeholder="Scan barcode / SKU">
                                    <span>|</span>
                                </label>
                                <div id="scan-product" class="scan-product" hidden></div>
                                <div id="scan-status" class="scan-status">Scan barcode/SKU produk untuk masuk nota.</div>
                            </div>
                            <div id="scan-barcode" class="scan-barcode">Barcode</div>
                        </div>
                        <button id="category-toggle" class="category-menu" type="button">Kategori</button>
                    </div>

                    <div class="chips" aria-label="Kategori produk">
                        <button class="chip active" type="button" data-category="Semua">Semua</button>
                        @foreach ($categories as $category)
                            <button class="chip" type="button" data-category="{{ $category->name }}">{{ $category->name }}</button>
                        @endforeach
                    </div>

                    <div id="product-grid" class="product-grid" aria-live="polite"></div>
                </section>

                <aside class="cart-panel" aria-label="Nota aktif">
                    <header class="cart-head">
                        <div class="cart-title">
                            <span class="icon-cell">#</span>
                            <div>
                                <p class="small">Nota aktif</p>
                                <h2>Nota aktif</h2>
                            </div>
                            <span id="cart-count" class="badge">0</span>
                        </div>
                        <button id="clear-order" class="clear-btn" type="button">Bersihkan</button>
                    </header>

                    <section class="cart-table">
                        <div class="cart-header-row" aria-hidden="true">
                            <span>Produk</span>
                            <span>Harga</span>
                            <span>Qty</span>
                            <span>Subtotal</span>
                            <span></span>
                        </div>
                        <div id="cart-list" aria-live="polite"></div>
                    </section>

                    <footer class="checkout-area">
                        <div class="customer-row">
                            <input id="customer" class="customer-input" type="text" placeholder="Nama pelanggan">
                            <input id="table-number" class="customer-input" type="text" placeholder="Meja nomor">
                        </div>
                        <textarea id="customer-note" class="customer-input customer-note" maxlength="255" rows="2" placeholder="Catatan pesanan, contoh: less sugar, tanpa es, alergi kacang"></textarea>
                        <div id="saved-orders" class="saved-orders" hidden></div>

                        <div class="discount-row">
                            <span class="icon-cell">%</span>
                            <input id="discount-percent" type="number" min="0" max="100" step="1" value="0" aria-label="Diskon persen">
                            <div class="money-input">
                                <span>Rp</span>
                                <input id="discount" type="number" min="0" step="1000" value="0" aria-label="Diskon rupiah">
                            </div>
                        </div>

                        <div>
                            <div class="total-row"><span>Subtotal</span><strong id="subtotal">Rp 0</strong></div>
                            <div class="total-row"><span>Diskon</span><strong id="discount-label">Rp 0</strong></div>
                            <div class="total-row"><span>PPN 11%</span><strong id="tax">Rp 0</strong></div>
                            <div class="total-row grand"><span>Total</span><strong id="grand-total">Rp 0</strong></div>
                        </div>

                        <p class="small">Metode Pembayaran</p>
                        <div class="payment-grid">
                            <button class="pay-btn active" type="button" data-payment="Tunai"><span>T</span>Tunai</button>
                        </div>

                        <div class="payment-status">
                            <div>
                                <strong id="payment-status-title">Tunai dipilih</strong>
                                <span id="payment-status-text" class="small">Masukkan nominal uang diterima dari pelanggan.</span>
                            </div>
                            <input id="payment-reference" placeholder="No. referensi" disabled>
                        </div>

                        <div class="pay-row">
                            <span>Bayar</span>
                            <div class="money-input">
                                <span>Rp</span>
                                <input id="cash" type="number" min="0" step="1000" value="0">
                            </div>
                            <span>Kembalian</span>
                            <strong id="change" class="change">Rp 0</strong>
                        </div>

                        <div class="action-row">
                            <button id="save-order" class="save-btn" type="button">Simpan</button>
                            <button id="hold-order" class="park-btn" type="button">Parkir</button>
                            <button id="checkout" class="checkout-btn" type="button">Bayar</button>
                        </div>
                    </footer>
                </aside>
            </section>

        </main>
    </div>

    <div id="toast" class="toast" role="status"></div>

    <div id="receipt-modal" class="modal" aria-hidden="true">
        <section class="receipt">
            <div class="receipt-header">
                @if ($store['logo_url'])
                    <img class="receipt-logo" src="{{ $store['logo_url'] }}" alt="{{ $store['name'] }} logo" style="width: 54px; height: 54px; border-radius: 8px; margin: 0 auto 8px;">
                @endif
                <h2 id="receipt-ticket">TRX-0001</h2>
                <strong>{{ $store['name'] }}</strong>
                <span>{{ $store['address'] }}</span>
            </div>
            <div id="receipt-body"></div>
            <div class="receipt-actions">
                <button id="close-receipt" class="clear-btn" type="button">Tutup</button>
                <button id="print-receipt" class="checkout-btn" type="button">Cetak</button>
            </div>
        </section>
    </div>

    <div id="qris-modal" class="modal" aria-hidden="true">
        <section class="qris-panel">
            <div>
                <h2>Scan Barcode Pembayaran</h2>
                <p class="small">Tunjukkan gambar ini ke pelanggan. Setelah pelanggan scan dan bayar, tekan sudah dibayar.</p>
            </div>
            <div class="qris-box">
                <img id="qris-image" alt="Barcode pembayaran" hidden>
                <div id="qris-code" class="qris-code" hidden></div>
            </div>
            <div class="receipt-line">
                <span>Total</span>
                <strong id="qris-amount">Rp 0</strong>
            </div>
            <div id="qris-status" class="qris-status">Menunggu pelanggan scan barcode pembayaran.</div>
            <div class="barcode-confirm-actions">
                <button id="qris-cancel" class="clear-btn" type="button">Batalkan</button>
                <button id="barcode-paid" class="checkout-btn" type="button">Sudah Dibayar</button>
            </div>
        </section>
    </div>

    <script>
        const products = @json($products);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const qrisChargeUrl = '{{ route('payments.qris.charge', [], false) }}';
        const qrisFinalizeUrl = '{{ route('payments.qris.finalize', [], false) }}';
        const paymentBarcodeUrl = @json($paymentBarcodeUrl);
        const taxRate = 0.11;
        const state = {
            category: 'Semua',
            orderType: 'Dine in',
            payment: 'Tunai',
            discountMode: 'amount',
            cart: new Map(),
            savedOrders: [],
            currentOrderId: null,
            qrisOrderId: null,
            qrisPollTimer: null,
        };

        const openOrdersUrl = '{{ route('orders.open', [], false) }}';
        const parkOrderUrl = '{{ route('orders.park', [], false) }}';
        const destroyOrderUrl = '{{ route('orders.open', [], false) }}';
        const rupiah = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
        const byId = (id) => document.getElementById(id);
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[match]));
        const initials = (name) => String(name).split(' ').slice(0, 2).map((word) => word[0]).join('').toUpperCase();

        const nodes = {
            grid: byId('product-grid'),
            cart: byId('cart-list'),
            cartCount: byId('cart-count'),
            savedOrders: byId('saved-orders'),
            search: byId('search'),
            barcodeScan: byId('barcode-scan'),
            scanProduct: byId('scan-product'),
            scanBarcode: byId('scan-barcode'),
            scanStatus: byId('scan-status'),
            subtotal: byId('subtotal'),
            discountPercent: byId('discount-percent'),
            discount: byId('discount'),
            discountLabel: byId('discount-label'),
            tax: byId('tax'),
            grandTotal: byId('grand-total'),
            cash: byId('cash'),
            change: byId('change'),
            paymentStatusTitle: byId('payment-status-title'),
            paymentStatusText: byId('payment-status-text'),
            paymentReference: byId('payment-reference'),
            customer: byId('customer'),
            customerNote: byId('customer-note'),
            tableNumber: byId('table-number'),
            toast: byId('toast'),
            modal: byId('receipt-modal'),
            receiptTicket: byId('receipt-ticket'),
            receiptBody: byId('receipt-body'),
            qrisModal: byId('qris-modal'),
            qrisImage: byId('qris-image'),
            qrisCode: byId('qris-code'),
            qrisAmount: byId('qris-amount'),
            qrisStatus: byId('qris-status'),
            qrisCancel: byId('qris-cancel'),
            barcodePaid: byId('barcode-paid'),
        };

        const paymentCopy = {
            Tunai: {
                title: 'Tunai dipilih',
                text: 'Masukkan nominal uang diterima dari pelanggan.',
                reference: false,
            },
            Barcode: {
                title: 'Barcode dipilih',
                text: 'Tekan bayar untuk menampilkan barcode pembayaran ke pelanggan.',
                reference: false,
            },
        };

        async function loadSavedOrdersFromServer() {
            try {
                const response = await fetch(openOrdersUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const payload = await response.json();
                state.savedOrders = Array.isArray(payload.orders) ? payload.orders : [];
            } catch (error) {
                state.savedOrders = [];
            }
            renderSavedOrders();
        }

        function totals() {
            const items = [...state.cart.values()];
            const subtotal = items.reduce((sum, item) => sum + item.price * item.qty, 0);
            const percent = Math.min(Math.max(Number(nodes.discountPercent.value || 0), 0), 100);
            const percentDiscount = Math.round(subtotal * (percent / 100));
            const manualDiscount = Number(nodes.discount.value || 0);
            const rawDiscount = state.discountMode === 'percent' ? percentDiscount : manualDiscount;
            const discount = Math.min(rawDiscount, subtotal);
            const taxable = Math.max(subtotal - discount, 0);
            const tax = taxable * taxRate;
            const total = taxable + tax;
            const cash = state.payment === 'Tunai'
                ? Number(nodes.cash.value || 0)
                : Math.round(total);

            return {
                items,
                subtotal,
                discount,
                tax,
                total,
                cash,
                change: state.payment === 'Tunai' ? Math.max(cash - total, 0) : 0,
                percent,
                percentDiscount,
            };
        }

        function syncPaymentUi(total) {
            const copy = paymentCopy[state.payment] || paymentCopy.Tunai;
            const isCash = state.payment === 'Tunai';

            nodes.paymentStatusTitle.textContent = copy.title;
            nodes.paymentStatusText.textContent = copy.text;
            nodes.paymentReference.disabled = !copy.reference;
            nodes.paymentReference.placeholder = copy.reference ? 'No. referensi' : 'Tidak perlu referensi';
            nodes.cash.readOnly = !isCash;

            if (!isCash) {
                nodes.cash.value = Math.round(total);
            }
        }

        function filteredProducts() {
            const keyword = nodes.search.value.trim().toLowerCase();

            return products
                .filter((product) => {
                    const matchesCategory = state.category === 'Semua' || product.category === state.category;
                    const matchesKeyword = [product.sku, product.barcode, product.name, product.category]
                        .join(' ')
                        .toLowerCase()
                        .includes(keyword);

                    return matchesCategory && matchesKeyword;
                })
                .sort((a, b) => a.name.localeCompare(b.name));
        }

        function renderProducts() {
            const list = filteredProducts();

            if (!list.length) {
                nodes.grid.innerHTML = '<div class="empty">Produk tidak ditemukan.</div>';
                return;
            }

            nodes.grid.innerHTML = list.map((product) => {
                const inCart = state.cart.get(product.sku)?.qty || 0;
                const remaining = product.stock - inCart;
                const disabled = remaining <= 0 ? 'disabled' : '';
                const productLabel = product.is_bundle ? 'Paket' : (product.tag || product.unit);
                const barcodeLabel = product.barcode ? ` / Barcode ${escapeHtml(product.barcode)}` : '';
                const packageLine = product.is_bundle && product.package_contents
                    ? `<p class="small">${escapeHtml(product.package_contents)}</p>`
                    : '';
                const barcodeImage = product.barcode_image_url
                    ? `<div class="product-barcode"><img src="${escapeHtml(product.barcode_image_url)}" alt="Barcode ${escapeHtml(product.barcode)}"></div>`
                    : '';

                return `
                    <article class="product-card">
                        <div class="product-visual" style="--tile-color: ${escapeHtml(product.color)};">
                            ${product.image_url ? `<img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}">` : ''}
                            ${barcodeImage}
                            <span>${escapeHtml(initials(product.name))}</span>
                        </div>
                        <div class="product-info">
                            <div>
                                <h3 title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</h3>
                                <span class="price">${rupiah(product.price)}</span>
                                <p class="small">${escapeHtml(productLabel)} / Stok ${remaining} ${escapeHtml(product.unit)}</p>
                                ${packageLine}
                                <p class="small">${escapeHtml(product.sku)}${barcodeLabel}</p>
                            </div>
                            <button class="add-btn" type="button" data-add="${escapeHtml(product.sku)}" ${disabled}>+</button>
                        </div>
                    </article>
                `;
            }).join('');
        }

        function renderCart() {
            const data = totals();
            const count = data.items.reduce((sum, item) => sum + item.qty, 0);

            syncPaymentUi(data.total);
            if (state.discountMode === 'percent') {
                nodes.discount.value = data.percentDiscount;
            }
            nodes.cartCount.textContent = count;
            nodes.subtotal.textContent = rupiah(data.subtotal);
            nodes.discountLabel.textContent = rupiah(data.discount);
            nodes.tax.textContent = rupiah(data.tax);
            nodes.grandTotal.textContent = rupiah(data.total);
            nodes.change.textContent = rupiah(data.change);

            if (!data.items.length) {
                nodes.cart.innerHTML = '<div class="empty">Nota aktif belum berisi item.</div>';
                renderProducts();
                return;
            }

            nodes.cart.innerHTML = data.items.map((item) => {
                const image = item.image_url
                    ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}">`
                    : escapeHtml(initials(item.name));
                const packageLine = item.is_bundle && item.package_contents
                    ? `<p class="small">${escapeHtml(item.package_contents)}</p>`
                    : '';

                return `
                <article class="cart-row">
                    <div class="cart-product">
                        <div class="cart-thumb" style="--tile-color: ${escapeHtml(item.color)};">${image}</div>
                        <div>
                            <strong>${escapeHtml(item.name)}</strong>
                            ${packageLine}
                            <p class="small">${escapeHtml(item.sku)}</p>
                        </div>
                    </div>
                    <span>${rupiah(item.price)}</span>
                    <div class="qty-control">
                        <button type="button" data-dec="${escapeHtml(item.sku)}">-</button>
                        <span>${item.qty}</span>
                        <button type="button" data-inc="${escapeHtml(item.sku)}">+</button>
                    </div>
                    <strong>${rupiah(item.price * item.qty)}</strong>
                    <button class="remove-btn" type="button" data-remove="${escapeHtml(item.sku)}">Hapus</button>
                </article>
            `;
            }).join('');

            renderProducts();
        }

        function renderSavedOrders() {
            if (!state.savedOrders.length) {
                nodes.savedOrders.hidden = true;
                nodes.savedOrders.innerHTML = '';
                return;
            }

            nodes.savedOrders.hidden = false;
            nodes.savedOrders.innerHTML = state.savedOrders.map((order) => `
                <div class="saved-order">
                    <div>
                        <strong>${escapeHtml(order.label)}</strong>
                        ${order.customerNote ? `<p class="small">Catatan: ${escapeHtml(order.customerNote)}</p>` : ''}
                    </div>
                    <button type="button" data-load-order="${escapeHtml(order.id)}">Muat</button>
                    <button type="button" data-delete-order="${escapeHtml(order.id)}">Hapus</button>
                </div>
            `).join('');
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

        function normalizedCode(value) {
            return String(value || '').trim().toLowerCase();
        }

        function findProductByScan(value) {
            const code = normalizedCode(value);

            if (!code) return null;

            return products.find((product) =>
                normalizedCode(product.barcode) === code || normalizedCode(product.sku) === code
            );
        }

        function scanBarcode() {
            const value = nodes.barcodeScan.value;
            const product = findProductByScan(value);

            if (!product) {
                nodes.scanProduct.hidden = true;
                nodes.scanBarcode.textContent = 'Tidak ditemukan';
                nodes.scanStatus.textContent = 'Barcode atau SKU tidak ditemukan.';
                showToast('Barcode atau SKU tidak ditemukan');
                nodes.barcodeScan.select();
                return;
            }

            addItem(product.sku);
            renderScanPreview(product, 'Produk masuk nota');
            nodes.barcodeScan.value = '';
            showToast(`${product.name} masuk nota`);
        }

        function renderScanPreview(product, message = 'Siap scan') {
            const image = product.image_url
                ? `<img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}">`
                : escapeHtml(initials(product.name));
            const barcode = product.barcode_image_url
                ? `<img src="${escapeHtml(product.barcode_image_url)}" alt="Barcode ${escapeHtml(product.barcode)}">`
                : 'Belum ada barcode';

            nodes.scanProduct.hidden = false;
            nodes.scanProduct.innerHTML = `
                <div class="scan-thumb">${image}</div>
                <div>
                    <h3 title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</h3>
                    <div class="small">${escapeHtml(product.sku)}${product.barcode ? ` / ${escapeHtml(product.barcode)}` : ''}</div>
                    <div class="small">${rupiah(product.price)} / Stok ${product.stock}</div>
                </div>
            `;
            nodes.scanBarcode.innerHTML = barcode;
            nodes.scanStatus.textContent = message;
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

        function resetOrder() {
            state.cart.clear();
            state.currentOrderId = null;
            nodes.discountPercent.value = 0;
            nodes.discount.value = 0;
            state.discountMode = 'amount';
            nodes.cash.value = 0;
            nodes.paymentReference.value = '';
            nodes.customer.value = '';
            nodes.customerNote.value = '';
            nodes.tableNumber.value = '';
            renderCart();
        }

        function currentOrderSnapshot() {
            const data = totals();

            return {
                order_id: state.currentOrderId,
                customer_name: nodes.customer.value || null,
                customer_note: nodes.customerNote.value || null,
                table_number: nodes.tableNumber.value || null,
                cashier_name: '{{ $shift['cashier'] }}',
                order_type: state.orderType,
                discount: Math.round(data.discount),
                items: data.items.map((item) => ({
                    product_id: item.id,
                    quantity: item.qty,
                })),
            };
        }

        async function saveCurrentOrder({ clearAfterSave = false } = {}) {
            if (!state.cart.size) {
                showToast('Nota aktif masih kosong');
                return;
            }

            if (!nodes.tableNumber.value.trim()) {
                showToast('Isi nomor meja sebelum simpan order');
                return;
            }

            const button = clearAfterSave ? byId('hold-order') : byId('save-order');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Menyimpan...';

            try {
                const response = await fetch(parkOrderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(currentOrderSnapshot()),
                });
                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Order gagal disimpan'];
                    showToast(errors[0]);
                    return;
                }

                state.currentOrderId = payload.order.id;
                await loadSavedOrdersFromServer();

                if (clearAfterSave) {
                    resetOrder();
                    showToast('Order meja diparkir di database');
                    return;
                }

                showToast('Order meja tersimpan di database');
            } catch (error) {
                showToast('Koneksi ke server gagal');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        }

        function loadSavedOrder(id) {
            const order = state.savedOrders.find((item) => item.id === id);

            if (!order) {
                showToast('Order tersimpan tidak ditemukan');
                return;
            }

            state.cart.clear();
            order.items.forEach((item) => {
                const product = products.find((entry) => entry.id === item.product_id || entry.sku === item.sku);
                const qty = product ? Math.min(item.qty || item.quantity, product.stock) : 0;

                if (product && qty > 0) {
                    state.cart.set(product.sku, {
                        ...product,
                        qty,
                    });
                }
            });

            state.currentOrderId = order.id;
            nodes.customer.value = order.customer || '';
            nodes.customerNote.value = order.customerNote || '';
            nodes.tableNumber.value = order.tableNumber || '';
            nodes.discountPercent.value = order.discountPercent || 0;
            nodes.discount.value = order.discount || 0;
            state.discountMode = order.discountMode || (Number(order.discountPercent || 0) > 0 ? 'percent' : 'amount');
            nodes.cash.value = order.cash || 0;
            nodes.paymentReference.value = order.paymentReference || '';
            state.payment = order.payment || 'Tunai';

            document.querySelectorAll('[data-payment]').forEach((button) => {
                button.classList.toggle('active', button.dataset.payment === state.payment);
            });

            renderCart();
            showToast('Order dimuat ke keranjang');
        }

        async function deleteSavedOrder(id) {
            try {
                const response = await fetch(`${destroyOrderUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const payload = await response.json();

                if (!response.ok) {
                    showToast(payload.message || 'Order gagal dihapus');
                    return;
                }

                if (String(state.currentOrderId) === String(id)) {
                    resetOrder();
                }

                await loadSavedOrdersFromServer();
                showToast('Order meja dihapus');
            } catch (error) {
                showToast('Koneksi ke server gagal');
            }
        }

        function showToast(message) {
            nodes.toast.textContent = message;
            nodes.toast.classList.add('show');
            window.setTimeout(() => nodes.toast.classList.remove('show'), 1800);
        }

        function checkoutPayload(data, overrides = {}) {
            return {
                order_id: state.currentOrderId,
                customer_name: nodes.customer.value || null,
                customer_note: nodes.customerNote.value || null,
                table_number: nodes.tableNumber.value || null,
                cashier_name: '{{ $shift['cashier'] }}',
                order_type: state.orderType,
                payment_method: state.payment,
                payment_reference: nodes.paymentReference.value || null,
                discount: data.discount,
                paid_amount: state.payment === 'Tunai' ? data.cash : Math.round(data.total),
                items: data.items.map((item) => ({
                    product_id: item.id,
                    quantity: item.qty,
                })),
                ...overrides,
            };
        }

        async function checkout() {
            const data = totals();

            if (!data.items.length) {
                showToast('Nota aktif masih kosong');
                return;
            }

            if (state.payment === 'Tunai' && data.cash < data.total) {
                showToast('Nominal bayar kurang');
                return;
            }

            if (state.payment === 'Barcode') {
                startBarcodeCheckout(data);
                return;
            }

            byId('checkout').disabled = true;
            byId('checkout').textContent = 'Menyimpan...';

            try {
                const sale = await submitPaidSale(data);
                showReceipt(sale);
                await loadSavedOrdersFromServer();
            } catch (error) {
                showToast(error.message || 'Koneksi ke server gagal');
            } finally {
                byId('checkout').disabled = false;
                byId('checkout').textContent = 'Bayar';
            }
        }

        async function submitPaidSale(data, overrides = {}) {
            const response = await fetch('{{ route('sales.store', [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(checkoutPayload(data, overrides)),
            });

            const payload = await response.json();

            if (!response.ok) {
                const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Transaksi gagal disimpan'];
                throw new Error(errors[0]);
            }

            return payload.sale;
        }

        function startBarcodeCheckout(data) {
            if (!paymentBarcodeUrl) {
                showToast('Barcode pembayaran belum diatur di Settings');
                return;
            }

            nodes.qrisAmount.textContent = rupiah(data.total);
            nodes.qrisStatus.textContent = 'Menunggu pelanggan scan barcode pembayaran.';
            nodes.qrisImage.src = paymentBarcodeUrl;
            nodes.qrisImage.hidden = false;
            nodes.qrisCode.hidden = true;
            nodes.qrisModal.classList.add('open');
            nodes.qrisModal.setAttribute('aria-hidden', 'false');
            nodes.barcodePaid.disabled = false;
            nodes.barcodePaid.textContent = 'Sudah Dibayar';
        }

        async function confirmBarcodePayment() {
            const data = totals();

            if (!data.items.length) {
                closeQrisModal();
                showToast('Nota aktif masih kosong');
                return;
            }

            nodes.barcodePaid.disabled = true;
            nodes.barcodePaid.textContent = 'Menyimpan...';
            nodes.qrisStatus.textContent = 'Menyimpan transaksi barcode...';

            try {
                const sale = await submitPaidSale(data, {
                    payment_method: 'Barcode',
                    payment_reference: nodes.paymentReference.value || null,
                    paid_amount: Math.round(data.total),
                });

                nodes.qrisModal.classList.remove('open');
                nodes.qrisModal.setAttribute('aria-hidden', 'true');
                showReceipt(sale);
                await loadSavedOrdersFromServer();
            } catch (error) {
                nodes.qrisStatus.textContent = error.message || 'Transaksi barcode gagal disimpan';
                showToast(nodes.qrisStatus.textContent);
                nodes.barcodePaid.disabled = false;
                nodes.barcodePaid.textContent = 'Sudah Dibayar';
            }
        }

        async function startQrisCheckout(data) {
            byId('checkout').disabled = true;
            byId('checkout').textContent = 'Membuat QRIS...';

            try {
                const response = await fetch(qrisChargeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(checkoutPayload(data, {
                        payment_method: undefined,
                        payment_reference: undefined,
                        paid_amount: undefined,
                    })),
                });
                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'QRIS gagal dibuat'];
                    showToast(errors[0]);
                    resetCheckoutButton();
                    return;
                }

                state.qrisOrderId = payload.order_id;
                showQrisModal(payload);
                pollQrisPayment(payload.order_id);
            } catch (error) {
                showToast('Koneksi ke server gagal');
                resetCheckoutButton();
            }
        }

        function showQrisModal(payload) {
            nodes.qrisAmount.textContent = rupiah(payload.amount || 0);
            nodes.qrisStatus.textContent = 'Menunggu pembayaran...';
            nodes.qrisImage.hidden = true;
            nodes.qrisCode.hidden = true;

            if (payload.qr_url) {
                nodes.qrisImage.src = payload.qr_url;
                nodes.qrisImage.hidden = false;
            } else if (payload.qr_string) {
                nodes.qrisCode.textContent = payload.qr_string;
                nodes.qrisCode.hidden = false;
            } else {
                nodes.qrisCode.textContent = 'QRIS berhasil dibuat, tetapi kode QR tidak dikirim oleh Midtrans.';
                nodes.qrisCode.hidden = false;
            }

            nodes.qrisModal.classList.add('open');
            nodes.qrisModal.setAttribute('aria-hidden', 'false');
        }

        function pollQrisPayment(midtransOrderId) {
            clearQrisPoll();
            state.qrisPollTimer = window.setInterval(() => finalizeQrisPayment(midtransOrderId), 3000);
            window.setTimeout(() => finalizeQrisPayment(midtransOrderId), 1200);
        }

        async function finalizeQrisPayment(midtransOrderId) {
            try {
                const response = await fetch(qrisFinalizeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ midtrans_order_id: midtransOrderId }),
                });
                const payload = await response.json();

                if (response.status === 202) {
                    nodes.qrisStatus.textContent = 'Menunggu pembayaran...';
                    return;
                }

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Pembayaran QRIS gagal diverifikasi'];
                    nodes.qrisStatus.textContent = errors[0];
                    showToast(errors[0]);
                    closeQrisModal();
                    return;
                }

                clearQrisPoll();
                nodes.qrisModal.classList.remove('open');
                nodes.qrisModal.setAttribute('aria-hidden', 'true');
                state.qrisOrderId = null;
                showReceipt(payload.sale);
                await loadSavedOrdersFromServer();
            } catch (error) {
                nodes.qrisStatus.textContent = 'Koneksi ke server gagal, mencoba lagi...';
            }
        }

        function clearQrisPoll() {
            if (state.qrisPollTimer) {
                window.clearInterval(state.qrisPollTimer);
                state.qrisPollTimer = null;
            }
        }

        function closeQrisModal() {
            clearQrisPoll();
            state.qrisOrderId = null;
            nodes.qrisModal.classList.remove('open');
            nodes.qrisModal.setAttribute('aria-hidden', 'true');
            nodes.barcodePaid.disabled = false;
            nodes.barcodePaid.textContent = 'Sudah Dibayar';
            resetCheckoutButton();
        }

        function resetCheckoutButton() {
            byId('checkout').disabled = false;
            byId('checkout').textContent = 'Bayar';
        }

        function showReceipt(sale) {
            nodes.receiptTicket.textContent = sale.invoice_number;
            const itemRows = sale.items.map((item) => {
                const image = item.image_url
                    ? `<img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.product_name)}">`
                    : escapeHtml(initials(item.product_name));
                const packageLine = item.is_bundle && item.package_contents
                    ? `<span class="receipt-item-sub">${escapeHtml(item.package_contents)}</span>`
                    : '';

                return `
                <div class="receipt-item">
                    <div class="receipt-thumb">${image}</div>
                    <div>
                        <div class="receipt-item-main">
                            <span>${escapeHtml(item.product_name)}</span>
                            <strong>${rupiah(item.line_total)}</strong>
                        </div>
                        ${packageLine}
                        <span class="receipt-item-sub">${item.quantity} x ${rupiah(item.unit_price)}</span>
                    </div>
                </div>
            `;
            }).join('');
            const reference = sale.payment_reference
                ? `<div class="receipt-line"><span>Referensi</span><strong>${escapeHtml(sale.payment_reference)}</strong></div>`
                : '';
            const customerNote = sale.customer_note
                ? `<div class="receipt-line"><span>Catatan</span><strong>${escapeHtml(sale.customer_note)}</strong></div>`
                : '';

            nodes.receiptBody.innerHTML = `
                <div class="receipt-section">
                    <div class="receipt-line"><span>Waktu</span><strong>${escapeHtml(sale.paid_at)}</strong></div>
                    <div class="receipt-line"><span>Tipe</span><strong>${escapeHtml(sale.order_type)}</strong></div>
                    <div class="receipt-line"><span>Pelanggan</span><strong>${escapeHtml(sale.customer_name || 'Umum')}</strong></div>
                    <div class="receipt-line"><span>Meja</span><strong>${escapeHtml(sale.table_number || '-')}</strong></div>
                    <div class="receipt-line"><span>Kasir</span><strong>${escapeHtml(sale.cashier_name || '-')}</strong></div>
                    ${customerNote}
                </div>
                <div class="receipt-section">
                    ${itemRows}
                </div>
                <div class="receipt-section">
                    <div class="receipt-line"><span>Subtotal</span><strong>${rupiah(sale.subtotal)}</strong></div>
                    <div class="receipt-line"><span>Diskon</span><strong>${rupiah(sale.discount)}</strong></div>
                    <div class="receipt-line"><span>PPN 11%</span><strong>${rupiah(sale.tax)}</strong></div>
                    <div class="receipt-line receipt-total"><span>Total</span><strong>${rupiah(sale.total)}</strong></div>
                </div>
                <div class="receipt-section">
                    <div class="receipt-line"><span>Metode</span><strong>${escapeHtml(sale.payment_method)}</strong></div>
                    ${reference}
                    <div class="receipt-line"><span>Bayar</span><strong>${rupiah(sale.paid_amount)}</strong></div>
                    <div class="receipt-line"><span>Kembali</span><strong>${rupiah(sale.change_amount)}</strong></div>
                </div>
                <div class="receipt-footer">Terima kasih atas kunjungan Anda.</div>
            `;
            nodes.modal.classList.add('open');
            nodes.modal.setAttribute('aria-hidden', 'false');
        }

        document.querySelectorAll('.chip').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.chip').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.category = button.dataset.category;
                renderProducts();
            });
        });

        byId('category-toggle').addEventListener('click', () => {
            document.querySelector('.chips').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            showToast('Pilih kategori produk');
        });

        document.querySelectorAll('[data-payment]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-payment]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                state.payment = button.dataset.payment;
                renderCart();
                showToast(`${state.payment} dipilih`);
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

        nodes.savedOrders.addEventListener('click', (event) => {
            const load = event.target.closest('[data-load-order]');
            const remove = event.target.closest('[data-delete-order]');

            if (load) loadSavedOrder(load.dataset.loadOrder);
            if (remove) deleteSavedOrder(remove.dataset.deleteOrder);
        });

        nodes.search.addEventListener('input', renderProducts);
        nodes.barcodeScan.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                scanBarcode();
            }
        });
        nodes.discountPercent.addEventListener('input', () => {
            state.discountMode = Number(nodes.discountPercent.value || 0) > 0 ? 'percent' : 'amount';
            if (state.discountMode === 'amount') {
                nodes.discount.value = 0;
            }
            renderCart();
        });
        nodes.discount.addEventListener('input', () => {
            state.discountMode = 'amount';
            nodes.discountPercent.value = 0;
            renderCart();
        });
        nodes.cash.addEventListener('input', renderCart);

        byId('clear-order').addEventListener('click', resetOrder);
        byId('save-order').addEventListener('click', () => saveCurrentOrder());
        byId('hold-order').addEventListener('click', () => saveCurrentOrder({ clearAfterSave: true }));
        byId('checkout').addEventListener('click', checkout);
        nodes.qrisCancel.addEventListener('click', closeQrisModal);
        nodes.barcodePaid.addEventListener('click', confirmBarcodePayment);
        byId('close-receipt').addEventListener('click', () => {
            nodes.modal.classList.remove('open');
            nodes.modal.setAttribute('aria-hidden', 'true');
            window.location.reload();
        });
        byId('print-receipt').addEventListener('click', () => window.print());

        loadSavedOrdersFromServer();
        renderProducts();
        renderCart();
    </script>
</body>
</html>
