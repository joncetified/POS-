<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Support\CafeCatalog;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $store = CafeCatalog::store();
        $categories = collect();
        $products = collect();

        if (Schema::hasTable('categories') && Schema::hasTable('products')) {
            CafeCatalog::ensure();
            $hasProductBundles = Schema::hasTable('product_bundle_items');
            $hasBarcode = Schema::hasColumn('products', 'barcode');
            $hasPackageContents = Schema::hasColumn('products', 'package_contents');
            $hasImagePath = Schema::hasColumn('products', 'image_path');
            $hasIsBundle = Schema::hasColumn('products', 'is_bundle');

            $categories = Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            $productQuery = Product::query()->with(['category:id,name']);

            if ($hasProductBundles) {
                $productQuery->with(['bundleItems.component:id,name,stock']);
            }

            $products = $productQuery
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'barcode' => $hasBarcode ? $product->barcode : null,
                    'barcode_image_url' => $this->barcodeSvgDataUri($hasBarcode ? $product->barcode : null),
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'category_id' => $product->category_id,
                    'price' => $product->price,
                    'stock' => $hasProductBundles && $hasIsBundle ? $product->availableForSaleStock() : $product->stock,
                    'unit' => $product->unit,
                    'tag' => $product->tag,
                    'package_contents' => $hasPackageContents ? $product->package_contents : null,
                    'color' => $product->color,
                    'is_bundle' => $hasIsBundle ? $product->is_bundle : false,
                    'image_url' => $hasImagePath ? $product->imageUrl() : null,
                ])
                ->values();
        }

        $orders = 0;
        $revenue = 0;

        if (Schema::hasTable('sales')) {
            $todaySales = Sale::query()
                ->whereDate('paid_at', today())
                ->where('status', 'paid');

            $orders = (clone $todaySales)->count();
            $revenue = (clone $todaySales)->sum('total');
        }

        $shift = [
            'cashier' => $store['cashier'],
            'outlet' => $store['name'],
            'orders' => $orders,
            'revenue' => $revenue,
            'average' => $orders > 0 ? (int) round($revenue / $orders) : 0,
        ];

        $paymentBarcodeUrl = $store['payment_barcode_url'];

        return view('pos', compact('categories', 'products', 'shift', 'store', 'paymentBarcodeUrl'));
    }

    private function barcodeSvgDataUri(?string $value): ?string
    {
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
    }
}
