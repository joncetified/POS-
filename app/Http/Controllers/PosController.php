<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        CafeCatalog::ensure();

        $store = CafeCatalog::store();

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category?->name,
                'category_id' => $product->category_id,
                'price' => $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
                'tag' => $product->tag,
                'color' => $product->color,
                'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
            ])
            ->values();

        $todaySales = Sale::query()
            ->whereDate('paid_at', today())
            ->where('status', 'paid');

        $orders = (clone $todaySales)->count();
        $revenue = (clone $todaySales)->sum('total');

        $shift = [
            'cashier' => $store['cashier'],
            'outlet' => $store['name'],
            'orders' => $orders,
            'revenue' => $revenue,
            'average' => $orders > 0 ? (int) round($revenue / $orders) : 0,
        ];

        return view('pos', compact('categories', 'products', 'shift', 'store'));
    }
}
