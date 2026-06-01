<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Menu Meja - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f6f3ef; --surface: #fff; --ink: #24140c; --muted: #7c6b5c; --line: #e8ded3; --brown: #4b2308; --gold: #ffc94b; --green: #0b9f55; --red: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input, textarea { font: inherit; }
        .shell { width: min(1180px, calc(100% - 28px)); margin: 22px auto; display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; }
        .topbar, .panel, .cart { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(56, 28, 7, .07); }
        .topbar { grid-column: 1 / -1; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 18px; }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: clamp(1.45rem, 3vw, 2.1rem); }
        h2 { font-size: 1.1rem; }
        h3 { font-size: 1rem; }
        .muted { color: var(--muted); }
        .badge { display: inline-flex; align-items: center; min-height: 34px; border-radius: 999px; padding: 6px 12px; background: #fff6e5; border: 1px solid #f2d7aa; font-weight: 900; }
        .btn { min-height: 42px; border: 1px solid var(--brown); border-radius: 8px; padding: 9px 13px; color: #fff; background: var(--brown); cursor: pointer; font-weight: 850; }
        .btn.secondary { color: var(--brown); background: #fffaf2; border-color: #ead9c6; }
        .btn.gold { color: var(--ink); background: var(--gold); border-color: var(--gold); }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .catalog { display: grid; gap: 14px; }
        .panel { padding: 16px; display: grid; gap: 12px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .item { border: 1px solid var(--line); border-radius: 8px; padding: 14px; background: #fffaf2; display: grid; gap: 10px; min-height: 154px; }
        .item-top { display: grid; gap: 5px; align-content: start; }
        .price { font-weight: 950; }
        .stock { font-size: .88rem; color: var(--muted); }
        .qty { display: grid; grid-template-columns: 42px 1fr 42px; gap: 8px; align-items: center; margin-top: auto; }
        .qty button { min-height: 38px; border-radius: 8px; border: 1px solid var(--line); background: #fff; cursor: pointer; font-weight: 950; }
        .qty strong { text-align: center; }
        .cart { position: sticky; top: 16px; align-self: start; padding: 16px; display: grid; gap: 13px; }
        .cart-list { display: grid; gap: 9px; min-height: 120px; }
        .cart-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; border-bottom: 1px solid var(--line); padding-bottom: 9px; }
        .cart-row strong, .item h3 { overflow-wrap: anywhere; }
        .summary { display: grid; gap: 7px; border-top: 1px solid var(--line); padding-top: 12px; }
        .line { display: flex; justify-content: space-between; gap: 12px; }
        .total { font-size: 1.15rem; font-weight: 950; }
        .customer { display: grid; gap: 8px; }
        .customer input, .customer textarea { min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; }
        .customer textarea { resize: vertical; min-height: 74px; }
        .empty { min-height: 110px; display: grid; place-items: center; text-align: center; color: var(--muted); }
        .notice { border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; border-radius: 8px; padding: 12px; }
        .toast { position: fixed; left: 50%; bottom: 18px; transform: translateX(-50%) translateY(80px); opacity: 0; border-radius: 8px; background: var(--ink); color: #fff; padding: 12px 16px; transition: 180ms ease; z-index: 10; }
        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
        .logout-form { margin: 0; }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .cart { position: static; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .topbar { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Menu Meja Cafe</h1>
                @if ($canOrder)
                    <p class="muted">Pilih menu dengan tombol +, cek ringkasan, isi catatan jika perlu, lalu kirim ke kasir.</p>
                @else
                    <p class="muted">Ini katalog menu. Gunakan QR di meja untuk pesan makan/minum di tempat.</p>
                @endif
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                @if ($canOrder)
                    <span class="badge">Meja {{ $tableNumber }}</span>
                @endif
                @auth
                    <form class="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn secondary" type="submit">Logout</button>
                    </form>
                @endauth
            </div>
        </section>

        <section class="catalog">
            @foreach ($categories as $category)
                <section class="panel">
                    <h2>{{ $category->name }}</h2>
                    <div class="grid">
                        @forelse ($products->where('category_id', $category->id) as $product)
                            <article class="item">
                                <div class="item-top">
                                    <h3>{{ $product->name }}</h3>
                                    <p class="stock">{{ $product->sku }} / Stok {{ $product->stock }} {{ $product->unit }}</p>
                                    <p class="price">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                </div>
                                @if ($canOrder)
                                    <div class="qty">
                                        <button type="button" data-dec="{{ $product->id }}">-</button>
                                        <strong id="qty-{{ $product->id }}">0</strong>
                                        <button type="button" data-add="{{ $product->id }}">+</button>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="empty">Belum ada produk.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </section>

        <aside class="cart">
            <div>
                <p class="muted">Dine-in</p>
                <h2>Pesanan Meja</h2>
            </div>

            @if (! $canOrder)
                <div class="notice">Pelanggan pesan dari QR yang ditempel di meja.</div>
            @else
                <div class="notice">Catatan seperti less sugar, tanpa es, atau alergi akan terlihat di layar kasir.</div>
            @endif

            <div id="cart-list" class="cart-list">
                <div class="empty">Belum ada item pesanan.</div>
            </div>

            <div class="summary">
                <div class="line"><span>Subtotal</span><strong id="subtotal">Rp 0</strong></div>
                <div class="line"><span>PPN 11%</span><strong id="tax">Rp 0</strong></div>
                <div class="line total"><span>Total</span><strong id="total">Rp 0</strong></div>
            </div>

            @if ($canOrder)
                <div class="customer">
                    <input id="customer-name" type="text" maxlength="120" placeholder="Nama pelanggan (opsional)">
                    <textarea id="customer-note" maxlength="255" rows="3" placeholder="Catatan pesanan (opsional), contoh: less sugar, tanpa es"></textarea>
                    <button id="send-order" class="btn gold" type="button">Kirim ke Kasir</button>
                </div>
            @endif
        </aside>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    @php
        $menuProducts = $products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->stock,
        ])->values();
    @endphp

    <script>
        const canOrder = @json($canOrder);
        const products = @json($menuProducts);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const orderUrl = @json($canOrder ? route('customer.table.orders', ['tableNumber' => $tableNumber], false) : null);
        const cart = new Map();
        const rupiah = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)))}`;
        const byId = (id) => document.getElementById(id);
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (match) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[match]));

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

        function render() {
            const data = totals();

            products.forEach((product) => {
                const qtyNode = byId(`qty-${product.id}`);
                if (qtyNode) qtyNode.textContent = cart.get(product.id)?.qty || 0;
            });

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
                        <p class="muted">${item.qty} x ${rupiah(item.price)}</p>
                    </div>
                    <strong>${rupiah(item.price * item.qty)}</strong>
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

            render();
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
                render();
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

            if (add) changeQty(add.dataset.add, 1);
            if (dec) changeQty(dec.dataset.dec, -1);
        });

        if (canOrder) {
            byId('send-order').addEventListener('click', sendOrder);
        }

        render();
    </script>
</body>
</html>
