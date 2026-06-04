<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Menu Meja - {{ $store['name'] }}</title>
    <style>
        :root {
            --page: #ffd1b8;
            --surface: #fffdf9;
            --soft: #fff3ec;
            --ink: #2b201b;
            --muted: #9a8274;
            --line: #f0cdbd;
            --accent: #ff8655;
            --brown: #5b2a12;
            --green: #287967;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at 18% 0%, rgba(255, 255, 255, .55), transparent 30%),
                linear-gradient(120deg, #ffc6aa 0%, var(--page) 48%, #ffd9c7 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        button, input, textarea { font: inherit; }
        button { cursor: pointer; }
        h1, h2, h3, p { margin: 0; }
        .customer-menu-shell {
            width: min(1240px, calc(100% - 36px));
            margin: 20px auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 14px;
        }
        .menu-header,
        .catalog,
        .order-panel {
            border: 1px solid rgba(240, 205, 189, .95);
            border-radius: 8px;
            background: rgba(255, 253, 249, .96);
            box-shadow: 0 18px 44px rgba(91, 42, 18, .08);
        }
        .menu-header {
            grid-column: 1 / -1;
            min-height: 92px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 18px 20px;
        }
        .eyebrow { color: var(--muted); font-size: .86rem; }
        h1 { font-size: clamp(1.55rem, 2.7vw, 2.45rem); line-height: 1.04; letter-spacing: 0; }
        .header-copy { margin-top: 5px; color: var(--muted); line-height: 1.45; max-width: 760px; }
        .header-actions { display: flex; gap: 9px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
        .pill,
        .customer-account,
        .btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 9px 15px;
            font-weight: 900;
            text-decoration: none;
        }
        .pill { color: #fff; background: var(--accent); border-color: var(--accent); }
        .customer-account,
        .btn.secondary { color: var(--brown); background: var(--soft); }
        .btn.primary { color: #fff; background: var(--accent); border-color: var(--accent); box-shadow: 0 12px 24px rgba(255, 134, 85, .22); }
        .btn:disabled { opacity: .62; cursor: not-allowed; box-shadow: none; }
        .customer-account { gap: 9px; }
        .customer-account-avatar {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 10px;
            background: var(--brown);
            color: #fff;
            font-size: .78rem;
        }
        .customer-account-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .catalog {
            min-width: 0;
            padding: 18px;
            display: grid;
            gap: 14px;
        }
        .catalog-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 320px);
            gap: 12px;
            align-items: end;
        }
        .catalog-head h2 { font-size: 1.22rem; line-height: 1.1; }
        .search-field {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 0 16px;
            color: var(--ink);
            background: #fff;
            outline: none;
        }
        .search-field:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255, 134, 85, .16); }
        .category-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
            scrollbar-width: thin;
        }
        .category-tab {
            flex: 0 0 auto;
            min-height: 38px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--brown);
            padding: 8px 16px;
            font-weight: 900;
        }
        .category-tab.active { color: #fff; background: var(--accent); border-color: var(--accent); }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .product-card {
            min-width: 0;
            min-height: 152px;
            display: grid;
            grid-template-columns: 124px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            padding: 14px;
        }
        .product-media {
            position: relative;
            width: 124px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 8px;
            background: linear-gradient(135deg, #ffc4a5, #fff7ef);
        }
        .product-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .product-fallback {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, #ff9a67, #5b2a12);
            font-size: 1.35rem;
            font-weight: 950;
        }
        .product-code {
            position: absolute;
            right: 8px;
            bottom: 8px;
            min-width: 38px;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: #fff;
            background: var(--accent);
            font-size: .78rem;
            font-weight: 950;
            padding: 4px 9px;
        }
        .product-info { min-width: 0; display: grid; gap: 6px; }
        .product-info h3 {
            font-size: 1rem;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }
        .meta { color: var(--muted); font-size: .82rem; line-height: 1.35; }
        .price { color: #ff7041; font-size: 1.05rem; font-weight: 950; }
        .qty-control {
            width: 100%;
            display: grid;
            grid-template-columns: 36px minmax(38px, 1fr) 36px;
            gap: 7px;
            align-items: center;
            margin-top: 4px;
        }
        .qty-control button {
            min-height: 34px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff8f2;
            color: var(--brown);
            font-weight: 950;
        }
        .qty-control strong { text-align: center; }
        .order-panel {
            position: sticky;
            top: 14px;
            align-self: start;
            max-height: calc(100vh - 28px);
            min-width: 0;
            display: grid;
            grid-template-rows: auto minmax(130px, 1fr) auto;
            overflow: hidden;
        }
        .order-head,
        .order-form,
        .order-list { padding: 18px; }
        .order-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            border-bottom: 1px solid var(--line);
        }
        .order-title { display: flex; gap: 10px; align-items: center; min-width: 0; }
        .order-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--soft);
            color: var(--brown);
            font-weight: 950;
        }
        .order-count {
            min-width: 34px;
            min-height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--accent);
            font-weight: 950;
        }
        .order-list {
            min-height: 0;
            overflow-y: auto;
            display: grid;
            align-content: start;
            gap: 10px;
        }
        .empty {
            min-height: 140px;
            display: grid;
            place-items: center;
            border: 1px dashed var(--line);
            border-radius: 8px;
            color: var(--muted);
            text-align: center;
            padding: 16px;
        }
        .cart-row {
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: start;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fffaf6;
            padding: 11px;
        }
        .cart-row strong { overflow-wrap: anywhere; }
        .cart-row .amount { white-space: nowrap; }
        .summary {
            display: grid;
            gap: 7px;
            border-top: 1px solid var(--line);
            padding-top: 12px;
        }
        .line { display: flex; justify-content: space-between; gap: 14px; }
        .line strong { white-space: nowrap; }
        .total { font-size: 1.12rem; font-weight: 950; }
        .order-form {
            display: grid;
            gap: 10px;
            border-top: 1px solid var(--line);
            background: rgba(255, 250, 246, .82);
        }
        .field {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 10px 14px;
            background: #fff;
            outline: none;
        }
        textarea.field {
            min-height: 72px;
            border-radius: 8px;
            resize: vertical;
        }
        .success-box {
            display: none;
            border: 1px solid rgba(40, 121, 103, .35);
            border-radius: 8px;
            background: #eefaf5;
            color: #174e42;
            padding: 12px;
            line-height: 1.45;
        }
        .success-box.show { display: block; }
        .notice {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--soft);
            color: var(--brown);
            padding: 12px;
            line-height: 1.45;
        }
        .toast {
            position: fixed;
            left: 50%;
            bottom: 18px;
            z-index: 20;
            max-width: calc(100% - 28px);
            transform: translateX(-50%) translateY(80px);
            opacity: 0;
            border-radius: 999px;
            background: var(--ink);
            color: #fff;
            padding: 12px 16px;
            transition: 180ms ease;
            font-weight: 850;
        }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .logout-form { margin: 0; }
        [hidden] { display: none !important; }
        @media (max-width: 1080px) {
            .customer-menu-shell { grid-template-columns: 1fr; }
            .order-panel { position: static; max-height: none; grid-template-rows: auto; }
            .order-list { max-height: 420px; }
        }
        @media (max-width: 760px) {
            .customer-menu-shell { width: min(100% - 20px, 620px); margin: 10px auto; }
            .menu-header,
            .catalog-head { grid-template-columns: 1fr; }
            .header-actions { justify-content: flex-start; }
            .product-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 460px) {
            .menu-header,
            .catalog,
            .order-head,
            .order-form,
            .order-list { padding: 14px; }
            .product-card { grid-template-columns: 96px minmax(0, 1fr); gap: 10px; padding: 10px; }
            .product-media { width: 96px; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="customer-menu-shell">
        <section class="menu-header">
            <div>
                <p class="eyebrow">{{ $store['name'] }}</p>
                <h1>{{ $canOrder ? 'Menu Meja ' . $tableNumber : 'Menu Cafe' }}</h1>
                <p class="header-copy">
                    @if ($canOrder)
                        Pilih menu, cek total, isi nama atau catatan, lalu kirim ke kasir. Pembayaran diselesaikan di kasir.
                    @else
                        Ini katalog menu. Untuk pesan langsung, scan QR yang ditempel di meja cafe.
                    @endif
                </p>
            </div>
            <div class="header-actions">
                @if ($canOrder)
                    <span class="pill">Meja {{ $tableNumber }}</span>
                @endif
                @auth
                    <a class="customer-account" href="{{ route('profile.edit') }}">
                        <span class="customer-account-avatar">
                            @if (auth()->user()->avatar_path)
                                <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}">
                            @else
                                {{ collect(explode(' ', auth()->user()->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}
                            @endif
                        </span>
                        Profil Saya
                    </a>
                    <form class="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit">Logout</button>
                    </form>
                @endauth
            </div>
        </section>

        <section class="catalog">
            <div class="catalog-head">
                <div>
                    <p class="eyebrow">{{ $store['address'] ?? 'Jl. Kopi Nusantara No. 8, Batam' }}</p>
                    <h2>Semua Produk</h2>
                </div>
                <input id="search" class="search-field" type="search" placeholder="Cari produk...">
            </div>

            <div class="category-tabs" aria-label="Kategori produk">
                <button class="category-tab active" type="button" data-category="all">Semua</button>
                @foreach ($categories as $category)
                    <button class="category-tab" type="button" data-category="{{ $category->id }}">{{ $category->name }}</button>
                @endforeach
            </div>

            <div id="product-grid" class="product-grid"></div>
        </section>

        <aside class="order-panel">
            <div class="order-head">
                <div class="order-title">
                    <span class="order-mark">#</span>
                    <div>
                        <p class="eyebrow">Nota aktif</p>
                        <h2>Pesanan Meja</h2>
                    </div>
                </div>
                <span id="cart-count" class="order-count">0</span>
            </div>

            <div id="cart-list" class="order-list">
                <div class="empty">Belum ada item pesanan.</div>
            </div>

            <div class="order-form">
                <div id="success-box" class="success-box" role="status" aria-live="polite"></div>

                <div class="summary">
                    <div class="line"><span>Subtotal</span><strong id="subtotal">Rp 0</strong></div>
                    <div class="line"><span>PPN 11%</span><strong id="tax">Rp 0</strong></div>
                    <div class="line total"><span>Total</span><strong id="total">Rp 0</strong></div>
                </div>

                @if (! $canOrder)
                    <div class="notice">Pelanggan pesan dari QR yang ditempel di meja.</div>
                @else
                    <input id="customer-name" class="field" type="text" maxlength="120" placeholder="Nama pelanggan (opsional)">
                    <textarea id="customer-note" class="field" maxlength="255" rows="3" placeholder="Catatan pesanan, contoh: less sugar, tanpa es"></textarea>
                    <button id="send-order" class="btn primary" type="button">Kirim ke Kasir</button>
                @endif
            </div>
        </aside>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    @php
        $menuProducts = $products->map(fn ($product) => [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'category' => $product->category?->name,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->availableForSaleStock(),
            'unit' => $product->unit,
            'tag' => $product->tag,
            'is_bundle' => $product->is_bundle,
            'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
        ])->values();
    @endphp

    <script>
        const canOrder = @json($canOrder);
        const products = @json($menuProducts);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const orderUrl = @json($canOrder ? route('customer.table.orders', ['tableNumber' => $tableNumber], false) : null);
        const cart = new Map();
        const state = { category: 'all', search: '' };
        const rupiah = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
        const byId = (id) => document.getElementById(id);
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[match]));
        const initials = (name) => String(name || '').split(/\s+/).filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase() || '#';

        function showToast(message) {
            const toast = byId('toast');
            toast.textContent = message;
            toast.classList.add('show');
            window.setTimeout(() => toast.classList.remove('show'), 2200);
        }

        function totals() {
            const items = [...cart.values()];
            const subtotal = items.reduce((sum, item) => sum + item.price * item.qty, 0);
            const tax = Math.round(subtotal * 0.11);

            return { items, subtotal, tax, total: subtotal + tax };
        }

        function visibleProducts() {
            const query = state.search.trim().toLowerCase();
            return products.filter((product) => {
                const inCategory = state.category === 'all' || String(product.category_id) === state.category;
                const haystack = `${product.name} ${product.sku} ${product.category || ''} ${product.tag || ''}`.toLowerCase();
                return inCategory && (!query || haystack.includes(query));
            });
        }

        function renderProducts() {
            const grid = byId('product-grid');
            const rows = visibleProducts();

            if (!rows.length) {
                grid.innerHTML = '<div class="empty">Produk tidak ditemukan.</div>';
                return;
            }

            grid.innerHTML = rows.map((product) => {
                const image = product.image_url
                    ? `<img src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}">`
                    : `<div class="product-fallback">${escapeHtml(initials(product.name))}</div>`;
                const stockLabel = product.is_bundle ? `Paket / Stok ${product.stock}` : `${escapeHtml(product.tag || product.category || product.unit)} / Stok ${product.stock} ${escapeHtml(product.unit)}`;
                const disabled = !canOrder || product.stock <= 0;

                return `
                    <article class="product-card">
                        <div class="product-media">
                            ${image}
                            <span class="product-code">${escapeHtml(initials(product.name))}</span>
                        </div>
                        <div class="product-info">
                            <h3>${escapeHtml(product.name)}</h3>
                            <p class="price">${rupiah(product.price)}</p>
                            <p class="meta">${stockLabel}</p>
                            ${canOrder ? `
                                <div class="qty-control">
                                    <button type="button" data-dec="${product.id}" aria-label="Kurangi ${escapeHtml(product.name)}" ${disabled ? 'disabled' : ''}>-</button>
                                    <strong id="qty-${product.id}">0</strong>
                                    <button type="button" data-add="${product.id}" aria-label="Tambah ${escapeHtml(product.name)}" ${disabled ? 'disabled' : ''}>+</button>
                                </div>
                            ` : ''}
                        </div>
                    </article>
                `;
            }).join('');

            renderCart();
        }

        function renderCart() {
            const data = totals();
            products.forEach((product) => {
                const qtyNode = byId(`qty-${product.id}`);
                if (qtyNode) qtyNode.textContent = cart.get(product.id)?.qty || 0;
            });

            byId('cart-count').textContent = data.items.reduce((sum, item) => sum + item.qty, 0);
            byId('subtotal').textContent = rupiah(data.subtotal);
            byId('tax').textContent = rupiah(data.tax);
            byId('total').textContent = rupiah(data.total);

            if (!data.items.length) {
                byId('cart-list').innerHTML = '<div class="empty">Belum ada item pesanan.</div>';
                return;
            }

            byId('cart-list').innerHTML = data.items.map((item) => `
                <div class="cart-row">
                    <div>
                        <strong>${escapeHtml(item.name)}</strong>
                        <p class="meta">${item.qty} x ${rupiah(item.price)}</p>
                    </div>
                    <strong class="amount">${rupiah(item.price * item.qty)}</strong>
                </div>
            `).join('');
        }

        function changeQty(productId, diff) {
            const product = products.find((item) => item.id === Number(productId));
            if (!product || !canOrder) return;

            const current = cart.get(product.id) || { ...product, qty: 0 };
            const nextQty = current.qty + diff;

            if (nextQty <= 0) {
                cart.delete(product.id);
            } else if (nextQty > product.stock) {
                showToast('Stok tidak mencukupi');
            } else {
                cart.set(product.id, { ...current, qty: nextQty });
            }

            renderCart();
        }

        function showOrderSuccess(order) {
            const box = byId('success-box');
            box.innerHTML = `
                <strong>Pesanan masuk ke kasir.</strong><br>
                Meja ${escapeHtml(order.table_number || '-')} - Total ${rupiah(order.total || 0)}.
                Tunggu kasir memuat nota dan menyelesaikan pembayaran.
            `;
            box.classList.add('show');
        }

        async function sendOrder() {
            const data = totals();

            if (!data.items.length) {
                showToast('Pilih menu dulu');
                return;
            }

            const button = byId('send-order');
            button.disabled = true;
            button.textContent = 'Mengirim ke kasir...';

            try {
                const response = await fetch(orderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        customer_name: byId('customer-name').value || null,
                        customer_note: byId('customer-note').value || null,
                        items: data.items.map((item) => ({
                            product_id: item.id,
                            quantity: item.qty,
                        })),
                    }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [payload.message || 'Pesanan gagal masuk ke kasir'];
                    showToast(errors[0]);
                    return;
                }

                cart.clear();
                byId('customer-note').value = '';
                renderCart();
                showOrderSuccess(payload.order || {});
                showToast('Pesanan meja sudah masuk ke kasir');
            } catch (error) {
                showToast('Koneksi ke server gagal');
            } finally {
                button.disabled = false;
                button.textContent = 'Kirim ke Kasir';
            }
        }

        document.addEventListener('click', (event) => {
            const add = event.target.closest('[data-add]');
            const dec = event.target.closest('[data-dec]');
            const category = event.target.closest('[data-category]');

            if (add) changeQty(add.dataset.add, 1);
            if (dec) changeQty(dec.dataset.dec, -1);
            if (category) {
                state.category = category.dataset.category;
                document.querySelectorAll('[data-category]').forEach((button) => button.classList.toggle('active', button === category));
                renderProducts();
            }
        });

        byId('search').addEventListener('input', (event) => {
            state.search = event.target.value;
            renderProducts();
        });

        if (canOrder) {
            byId('send-order').addEventListener('click', sendOrder);
        }

        renderProducts();
    </script>
</body>
</html>
