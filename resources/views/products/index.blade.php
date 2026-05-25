<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk - {{ $store['name'] }}</title>
    <style>
        :root {
            --bg: #f3f5f7;
            --surface: #ffffff;
            --soft: #f8fafc;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--bg);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button,
        input,
        select {
            font: inherit;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: min(1180px, calc(100% - 32px));
            margin: 24px auto;
            display: grid;
            gap: 18px;
        }

        .topbar,
        .panel,
        .form-grid,
        .actions {
            display: grid;
            gap: 14px;
        }

        .topbar {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            padding: 16px 18px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            padding: 18px;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.07);
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        .muted {
            color: var(--muted);
        }

        .form-grid {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        label {
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        input,
        select {
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 12px;
            background: var(--surface);
            color: var(--ink);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            padding: 12px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
        }

        .row-form {
            display: contents;
        }

        .row-form input,
        .row-form select {
            width: 100%;
            min-width: 110px;
        }

        .btn {
            min-height: 42px;
            border: 0;
            border-radius: 8px;
            padding: 10px 12px;
            color: #fff;
            background: var(--primary);
            cursor: pointer;
            font-weight: 800;
            transition: background 160ms ease, transform 160ms ease;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn.secondary {
            color: var(--ink);
            background: var(--soft);
            border: 1px solid var(--line);
        }

        .btn.secondary:hover {
            background: #eef2f7;
        }

        .btn.danger {
            background: var(--danger);
        }

        .btn.danger:hover {
            background: #b91c1c;
        }

        .logout-form {
            margin: 0;
        }

        .logout-form .btn {
            width: 100%;
        }

        .status {
            padding: 10px 12px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            font-weight: 800;
        }

        .errors {
            padding: 10px 12px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            grid-template-columns: 1fr 1fr;
        }

        .pagination {
            margin-top: 14px;
        }

        @media (max-width: 900px) {
            .topbar,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="topbar">
            <div>
                <p class="muted">{{ $store['name'] }}</p>
                <h1>Produk</h1>
            </div>
            <a class="btn secondary" href="{{ route('pos.index') }}">Kembali ke Kasir</a>
            <form class="logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn secondary" type="submit">Logout</button>
            </form>
        </section>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="panel">
            <h2>Tambah Kategori</h2>
            <form class="form-grid" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <div class="field">
                    <label for="category-name">Nama kategori</label>
                    <input id="category-name" name="name" required>
                </div>
                <button class="btn" type="submit">Simpan Kategori</button>
            </form>
        </section>

        <section class="panel">
            <h2>Tambah Produk</h2>
            @if ($categories->isEmpty())
                <p class="muted">Buat kategori dulu sebelum menambahkan produk.</p>
            @else
            <form class="form-grid" method="POST" action="{{ route('products.store') }}">
                @csrf
                <div class="field">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" required>
                </div>
                <div class="field">
                    <label for="name">Nama</label>
                    <input id="name" name="name" required>
                </div>
                <div class="field">
                    <label for="price">Harga</label>
                    <input id="price" name="price" type="number" min="0" required>
                </div>
                <div class="field">
                    <label for="stock">Stok</label>
                    <input id="stock" name="stock" type="number" min="0" required>
                </div>
                <div class="field">
                    <label for="unit">Unit</label>
                    <input id="unit" name="unit" value="pcs" required>
                </div>
                <div class="field">
                    <label for="tag">Tag</label>
                    <input id="tag" name="tag">
                </div>
                <div class="field">
                    <label for="color">Warna</label>
                    <input id="color" name="color" value="#0f766e" required>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="btn" type="submit">Simpan Produk</button>
            </form>
            @endif
        </section>

        <section class="panel">
            <h2>Daftar Produk</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <form id="update-{{ $product->id }}" method="POST" action="{{ route('products.update', $product) }}">
                                        @csrf
                                        @method('PUT')
                                        <input name="sku" value="{{ $product->sku }}" required>
                                        <input name="name" value="{{ $product->name }}" required style="margin-top: 8px;">
                                        <input name="unit" value="{{ $product->unit }}" required style="margin-top: 8px;">
                                        <input name="tag" value="{{ $product->tag }}" placeholder="Tag" style="margin-top: 8px;">
                                        <input name="color" value="{{ $product->color }}" required style="margin-top: 8px;">
                                        <input type="hidden" name="is_active" value="0">
                                    </form>
                                </td>
                                <td>
                                    <select form="update-{{ $product->id }}" name="category_id" required>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($category->id === $product->category_id)>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input form="update-{{ $product->id }}" name="price" type="number" min="0" value="{{ $product->price }}" required>
                                </td>
                                <td>
                                    <input form="update-{{ $product->id }}" name="stock" type="number" min="0" value="{{ $product->stock }}" required>
                                </td>
                                <td>
                                    <label style="display: flex; gap: 8px; align-items: center; text-transform: none;">
                                        <input form="update-{{ $product->id }}" name="is_active" type="checkbox" value="1" @checked($product->is_active)>
                                        Aktif
                                    </label>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button form="update-{{ $product->id }}" class="btn" type="submit">Update</button>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Nonaktif</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                {{ $products->links() }}
            </div>
        </section>
    </main>
</body>
</html>
