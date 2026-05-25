<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CafeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ->with('category')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::query()->create($this->validated($request));

        return back()->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return back()->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return back()->with('status', 'Produk dinonaktifkan.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id ?? 'NULL';

        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku,' . $productId],
            'name' => ['required', 'string', 'max:160'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'tag' => ['nullable', 'string', 'max:40'],
            'color' => ['required', 'string', 'max:16'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
