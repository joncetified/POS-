<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CafeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        return view('products.index', [
            'store' => CafeCatalog::store(),
            'categories' => Category::query()->orderBy('name')->get(),
            'products' => Product::query()
                ->with(['category', 'bundleItems.component'])
                ->orderBy('name')
                ->paginate(15),
            'componentProducts' => Product::query()
                ->where('is_bundle', false)
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hasBundleItems = $request->has('bundle_items');
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        DB::transaction(function () use ($data, $hasBundleItems): void {
            $bundleItems = $data['bundle_items'];
            unset($data['bundle_items']);

            $product = Product::query()->create($data);
            $this->syncBundleItems($product, $bundleItems, $hasBundleItems);
        });

        return back()->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $hasBundleItems = $request->has('bundle_items');
        $data = $this->validated($request, $product);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        DB::transaction(function () use ($data, $product, $hasBundleItems): void {
            $bundleItems = $data['bundle_items'];
            unset($data['bundle_items']);

            $product->update($data);
            $this->syncBundleItems($product, $bundleItems, $hasBundleItems);
        });

        return back()->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id ?? 'NULL';

        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku,' . $productId],
            'barcode' => ['nullable', 'string', 'max:80', 'unique:products,barcode,' . $productId],
            'name' => ['required', 'string', 'max:160'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'tag' => ['nullable', 'string', 'max:40'],
            'package_contents' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:16'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_bundle' => ['nullable', 'boolean'],
            'bundle_items' => ['nullable', 'array'],
            'bundle_items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'bundle_items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false, 'is_bundle' => false, 'bundle_items' => []];
    }

    /**
     * @param array<int, array{product_id?: int|null, quantity?: int|null}> $bundleItems
     */
    private function syncBundleItems(Product $product, array $bundleItems, bool $hasBundleItems): void
    {
        if (! $product->is_bundle) {
            $product->bundleItems()->delete();

            return;
        }

        if (! $hasBundleItems) {
            return;
        }

        $items = collect($bundleItems)
            ->filter(fn (array $item) => ! empty($item['product_id']) && ! empty($item['quantity']))
            ->groupBy(fn (array $item) => (int) $item['product_id'])
            ->map(fn ($items, $productId) => [
                'component_product_id' => (int) $productId,
                'quantity' => (int) $items->sum('quantity'),
            ])
            ->reject(fn (array $item) => $item['component_product_id'] === $product->id)
            ->values();

        $componentIds = $items->pluck('component_product_id');
        $invalidComponents = Product::query()
            ->whereIn('id', $componentIds)
            ->where('is_bundle', true)
            ->exists();

        if ($invalidComponents) {
            throw ValidationException::withMessages([
                'bundle_items' => 'Komponen paket tidak boleh memakai produk paket lain.',
            ]);
        }

        $product->bundleItems()->delete();
        $items->each(fn (array $item) => $product->bundleItems()->create($item));
    }
}
