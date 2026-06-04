<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk - {{ $store['name'] }}</title>
    <style>
        :root { --bg: #f3f5f7; --surface: #fff; --soft: #f8fafc; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --primary: #0f766e; --primary-dark: #115e59; --danger: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input, select, textarea { font: inherit; }
        a { color: inherit; text-decoration: none; }
        .shell { width: min(1220px, calc(100% - 32px)); margin: 24px auto; display: grid; gap: 18px; }
        .topbar, .panel { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); box-shadow: 0 14px 30px rgba(15, 23, 42, .06); }
        .topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center; padding: 16px 18px; }
        .actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; }
        .panel { padding: 18px; display: grid; gap: 14px; }
        .split { display: grid; grid-template-columns: .82fr 1.18fr; gap: 16px; align-items: start; }
        h1, h2, p { margin: 0; }
        h1 { font-size: clamp(1.55rem, 2.4vw, 2rem); }
        h2 { font-size: 1.12rem; }
        .muted { color: var(--muted); }
        .btn { min-height: 40px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; background: var(--soft); color: var(--ink); cursor: pointer; font-weight: 850; display: inline-flex; align-items: center; justify-content: center; }
        .btn.primary { border-color: var(--primary); background: var(--primary); color: #fff; }
        .btn.danger { border-color: var(--danger); background: var(--danger); color: #fff; }
        .btn:hover { transform: translateY(-1px); }
        .form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .category-form { grid-template-columns: minmax(0, 1fr) auto; align-items: end; }
        .field { display: grid; gap: 6px; }
        label { color: var(--muted); font-size: .76rem; font-weight: 850; text-transform: uppercase; }
        input, select, textarea { width: 100%; min-height: 42px; border: 1px solid var(--line); border-radius: 8px; padding: 9px 11px; background: var(--surface); color: var(--ink); }
        textarea { min-height: 70px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 118, 110, .12); }
        .status, .errors { border-radius: 8px; padding: 11px 13px; font-weight: 850; }
        .status { background: #dcfce7; color: #166534; }
        .errors { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 12px 9px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: .76rem; text-transform: uppercase; }
        tbody tr:hover { background: var(--soft); }
        .product-fields { display: grid; grid-template-columns: minmax(120px, .72fr) minmax(190px, 1fr); gap: 8px; }
        .meta-fields { display: grid; grid-template-columns: minmax(100px, 1fr) minmax(92px, .8fr) minmax(92px, .8fr); gap: 8px; }
        .swatch-row { display: grid; grid-template-columns: 26px minmax(90px, 1fr); gap: 8px; align-items: center; margin-top: 8px; }
        .swatch { width: 26px; height: 26px; border-radius: 8px; border: 1px solid var(--line); background: var(--color); }
        .row-actions { display: grid; grid-template-columns: minmax(112px, 1fr) minmax(92px, .8fr); gap: 8px; min-width: 220px; }
        .row-actions form { width: 100%; }
        .row-actions .btn { width: 100%; min-width: 0; padding-inline: 10px; white-space: normal; }
        .active-label { display: inline-flex; gap: 8px; align-items: center; font-weight: 850; text-transform: none; color: var(--ink); }
        .bundle-toggle { display: inline-flex; gap: 8px; align-items: center; min-height: 42px; color: var(--ink); font-weight: 850; text-transform: none; }
        .bundle-editor { grid-column: 1 / -1; display: grid; gap: 8px; padding: 10px; border: 1px dashed var(--line); border-radius: 8px; background: #fffaf3; }
        .bundle-row { display: grid; grid-template-columns: minmax(0, 1fr) 76px; gap: 8px; align-items: center; }
        .bundle-list { margin-top: 8px; display: grid; gap: 4px; color: var(--muted); font-size: .84rem; }
        .badge { display: inline-flex; width: max-content; min-height: 28px; align-items: center; border-radius: 999px; padding: 4px 9px; background: #fff0e7; color: #d86635; font-weight: 900; font-size: .78rem; }
        .product-image-preview { width: 76px; aspect-ratio: 1; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 12px; background: #fff7f2; color: #fff; overflow: hidden; }
        .product-image-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .product-image-preview span { width: 100%; height: 100%; display: grid; place-items: center; background: var(--color, var(--primary)); font-weight: 950; }
        .image-field { display: grid; gap: 8px; }
        .image-field .product-image-preview { width: 92px; }
        .barcode-preview { margin-top: 8px; display: grid; gap: 5px; max-width: 210px; color: var(--muted); font-size: .78rem; }
        .barcode-preview img { width: 100%; min-height: 54px; border: 1px solid var(--line); border-radius: 8px; background: #fff; padding: 6px; object-fit: contain; }
        .pagination { margin-top: 12px; }
        .logout-form { margin: 0; }
        .logout-form .btn { width: 100%; }
        @media (max-width: 980px) { .topbar, .split, .form-grid, .category-form { grid-template-columns: 1fr; } .actions { justify-content: flex-start; } .table-wrap { overflow-x: auto; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php
        $barcodeSvg = function (?string $value): ?string {
            $value = strtoupper(trim((string) $value));
            if ($value === '') {
                return null;
            }

            $patterns = [
                '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
                '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
                '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
                'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
                'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
                'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
                'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
                'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
                'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
                '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
                '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
            ];

            $encoded = '*' . preg_replace('/[^0-9A-Z\-. $\/+%]/', '-', $value) . '*';
            $x = 10;
            $bars = '';
            foreach (str_split($encoded) as $char) {
                $pattern = $patterns[$char] ?? $patterns['-'];
                foreach (str_split($pattern) as $index => $widthCode) {
                    $width = $widthCode === 'w' ? 4 : 2;
                    if ($index % 2 === 0) {
                        $bars .= '<rect x="' . $x . '" y="8" width="' . $width . '" height="44" fill="#111"/>';
                    }
                    $x += $width;
                }
                $x += 2;
            }
            $width = max($x + 10, 180);
            $safeValue = e($value);
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="74" viewBox="0 0 ' . $width . ' 74">'
                . '<rect width="100%" height="100%" fill="#fff"/>'
                . $bars
                . '<text x="50%" y="68" text-anchor="middle" font-family="monospace" font-size="10" fill="#111">' . $safeValue . '</text>'
                . '</svg>';

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        };
    @endphp
    <main class="shell">
        <section class="topbar">
            <div class="staff-brand-wrap">
                @include('partials.staff-brand', ['store' => $store])
            </div>
            @include('partials.staff-nav')
        </section>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <section class="split">
            <div class="panel">
                <h2>Tambah Kategori</h2>
                <form class="form-grid category-form" method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="field">
                        <label for="category-name">Nama kategori</label>
                        <input id="category-name" name="name" required>
                    </div>
                    <button class="btn primary" type="submit">Simpan</button>
                </form>
            </div>

            <div class="panel">
                <h2>Tambah Produk</h2>
                @if ($categories->isEmpty())
                    <p class="muted">Buat kategori dulu sebelum menambahkan produk.</p>
                @else
                    <form class="form-grid" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
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
                            <label for="barcode">Barcode</label>
                            <input id="barcode" name="barcode" inputmode="numeric" placeholder="Scan / ketik barcode">
                        </div>
                        <div class="field">
                            <label for="name">Nama</label>
                            <input id="name" name="name" required>
                        </div>
                        <div class="field">
                            <label for="package_contents">Isi Paket</label>
                            <input id="package_contents" name="package_contents" placeholder="Contoh: Nasi + Ayam">
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
                        <div class="field">
                            <label for="image">Gambar produk</label>
                            <div class="image-field">
                                <div class="product-image-preview" data-image-preview="create">
                                    <span style="--color: #0f766e">IMG</span>
                                </div>
                                <input id="image" name="image" type="file" accept="image/*" data-image-input="create">
                            </div>
                        </div>
                        <label class="bundle-toggle">
                            <input name="is_bundle" type="checkbox" value="1">
                            Produk paket / promo
                        </label>
                        <p class="muted">Untuk paket, isi detailnya di kolom Isi Paket, contoh: Nasi + Ayam.</p>
                        <input type="hidden" name="is_active" value="1">
                        <button class="btn primary" type="submit">Simpan Produk</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="panel">
            <div>
                <h2>Daftar Produk</h2>
                <p class="muted">Perubahan harga dan stok langsung dipakai di POS.</p>
            </div>
            <div class="table-wrap">
                <table>
                    <colgroup>
                        <col style="width: 104px;">
                        <col style="width: 330px;">
                        <col style="width: 170px;">
                        <col style="width: 310px;">
                        <col style="width: 92px;">
                        <col style="width: 230px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga / Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <div class="product-image-preview">
                                        @if ($product->image_path)
                                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                                        @else
                                            <span style="--color: {{ $product->color }}">{{ collect(explode(' ', $product->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <form id="update-{{ $product->id }}" method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div class="product-fields">
                                            <input name="sku" value="{{ $product->sku }}" required aria-label="SKU">
                                            <input name="name" value="{{ $product->name }}" required aria-label="Nama produk">
                                        </div>
                                        <input name="barcode" value="{{ $product->barcode }}" placeholder="Barcode" style="margin-top: 8px;" aria-label="Barcode">
                                        <input name="package_contents" value="{{ $product->package_contents }}" placeholder="Isi Paket, contoh: Nasi + Ayam" style="margin-top: 8px;" aria-label="Isi Paket">
                                        <div class="barcode-preview">
                                            @if ($barcodeSvg($product->barcode))
                                                <img src="{{ $barcodeSvg($product->barcode) }}" alt="Barcode {{ $product->barcode }}">
                                            @else
                                                <span>Belum ada barcode.</span>
                                            @endif
                                        </div>
                                        <div class="swatch-row">
                                            <span class="swatch" style="--color: {{ $product->color }}"></span>
                                            <input name="color" value="{{ $product->color }}" required aria-label="Warna">
                                        </div>
                                        <input name="tag" value="{{ $product->tag }}" placeholder="Tag" style="margin-top: 8px;" aria-label="Tag">
                                        <div class="image-field" style="margin-top: 8px;">
                                            <label for="image-{{ $product->id }}">Gambar produk</label>
                                            <div class="product-image-preview" data-image-preview="{{ $product->id }}">
                                                @if ($product->image_path)
                                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                                                @else
                                                    <span style="--color: {{ $product->color }}">{{ collect(explode(' ', $product->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</span>
                                                @endif
                                            </div>
                                            <input id="image-{{ $product->id }}" name="image" type="file" accept="image/*" aria-label="Gambar produk" data-image-input="{{ $product->id }}">
                                        </div>
                                        <input type="hidden" name="is_bundle" value="0">
                                        <label class="bundle-toggle" style="margin-top: 8px;">
                                            <input name="is_bundle" type="checkbox" value="1" @checked($product->is_bundle)>
                                            Produk paket / promo
                                        </label>
                                        <p class="muted">Isi paket ditulis di kolom Isi Paket, contoh: Nasi + Ayam.</p>
                                        <input type="hidden" name="is_active" value="0">
                                    </form>
                                    @if ($product->is_bundle)
                                        <div class="bundle-list">
                                            <span class="badge">Paket</span>
                                            @if ($product->package_contents)
                                                <span>{{ $product->package_contents }}</span>
                                            @endif
                                            @foreach ($product->bundleItems as $item)
                                                <span>{{ $item->component?->name ?: 'Produk terhapus' }} x {{ $item->quantity }}</span>
                                            @endforeach
                                        </div>
                                    @endif
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
                                    <div class="meta-fields">
                                        <input form="update-{{ $product->id }}" name="price" type="number" min="0" value="{{ $product->price }}" required aria-label="Harga">
                                        <input form="update-{{ $product->id }}" name="stock" type="number" min="0" value="{{ $product->stock }}" required aria-label="Stok">
                                        <input form="update-{{ $product->id }}" name="unit" value="{{ $product->unit }}" required aria-label="Unit">
                                    </div>
                                </td>
                                <td>
                                    <label class="active-label">
                                        <input form="update-{{ $product->id }}" name="is_active" type="checkbox" value="1" @checked($product->is_active)>
                                        Aktif
                                    </label>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button form="update-{{ $product->id }}" class="btn primary" type="submit">Edit / Simpan</button>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit" onclick="return confirm('Hapus produk ini permanen?')">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Belum ada produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                {{ $products->links() }}
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-image-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const key = input.dataset.imageInput;
                const preview = document.querySelector(`[data-image-preview="${key}"]`);
                const file = input.files && input.files[0];

                if (!preview || !file) {
                    return;
                }

                const url = URL.createObjectURL(file);
                preview.innerHTML = `<img src="${url}" alt="Preview gambar produk">`;
                preview.querySelector('img').addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
            });
        });
    </script>
</body>
</html>
