<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class CafeCatalog
{
    public static function store(): array
    {
        $names = [
            'Kopi Senja Loka',
            'Ruang Seduh Tiga',
            'Akarasa Coffee',
            'Kedai Bara Pagi',
            'Nala Brew House',
            'Teras Rasa Cafe',
        ];

        $seed = config('app.key') ?: base_path();
        $name = config('store.name') ?: $names[abs(crc32($seed)) % count($names)];

        return [
            'name' => $name,
            'cashier' => config('store.cashier', 'Barista 01'),
            'address' => config('store.address', 'Jl. Kopi Nusantara No. 8, Jakarta'),
        ];
    }

    public static function ensure(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $categories = collect([
            ['name' => 'Espresso Bar', 'sort_order' => 1],
            ['name' => 'Manual Brew', 'sort_order' => 2],
            ['name' => 'Milk Coffee', 'sort_order' => 3],
            ['name' => 'Non Coffee', 'sort_order' => 4],
            ['name' => 'Pastry', 'sort_order' => 5],
        ])->mapWithKeys(function (array $category) {
            $model = Category::query()->firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category
            );

            return [$category['name'] => $model];
        });

        collect(self::products())->each(function (array $product) use ($categories) {
            $category = $categories->get($product['category']);

            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'stock' => $product['stock'],
                    'unit' => $product['unit'],
                    'tag' => $product['tag'],
                    'color' => $product['color'],
                    'is_active' => true,
                ]
            );
        });
    }

    private static function products(): array
    {
        return [
            ['category' => 'Espresso Bar', 'sku' => 'ESP-001', 'name' => 'Espresso Single', 'price' => 18000, 'stock' => 42, 'unit' => 'cup', 'tag' => 'Classic', 'color' => '#7c2d12'],
            ['category' => 'Espresso Bar', 'sku' => 'ESP-002', 'name' => 'Americano Hot', 'price' => 24000, 'stock' => 38, 'unit' => 'cup', 'tag' => 'Black', 'color' => '#334155'],
            ['category' => 'Manual Brew', 'sku' => 'BRW-001', 'name' => 'V60 Flores Bajawa', 'price' => 36000, 'stock' => 18, 'unit' => 'cup', 'tag' => 'Filter', 'color' => '#0f766e'],
            ['category' => 'Manual Brew', 'sku' => 'BRW-002', 'name' => 'Japanese Iced Coffee', 'price' => 38000, 'stock' => 21, 'unit' => 'cup', 'tag' => 'Cold', 'color' => '#2563eb'],
            ['category' => 'Milk Coffee', 'sku' => 'MLK-001', 'name' => 'Cappuccino', 'price' => 32000, 'stock' => 34, 'unit' => 'cup', 'tag' => 'Foamy', 'color' => '#a16207'],
            ['category' => 'Milk Coffee', 'sku' => 'MLK-002', 'name' => 'Kopi Susu Gula Aren', 'price' => 28000, 'stock' => 55, 'unit' => 'cup', 'tag' => 'Favorit', 'color' => '#be123c'],
            ['category' => 'Milk Coffee', 'sku' => 'MLK-003', 'name' => 'Iced Cafe Latte', 'price' => 33000, 'stock' => 29, 'unit' => 'cup', 'tag' => 'Iced', 'color' => '#0891b2'],
            ['category' => 'Non Coffee', 'sku' => 'NON-001', 'name' => 'Matcha Latte', 'price' => 34000, 'stock' => 24, 'unit' => 'cup', 'tag' => 'Tea', 'color' => '#15803d'],
            ['category' => 'Non Coffee', 'sku' => 'NON-002', 'name' => 'Chocolate Signature', 'price' => 32000, 'stock' => 27, 'unit' => 'cup', 'tag' => 'Cocoa', 'color' => '#78350f'],
            ['category' => 'Pastry', 'sku' => 'PST-001', 'name' => 'Butter Croissant', 'price' => 26000, 'stock' => 16, 'unit' => 'pcs', 'tag' => 'Fresh', 'color' => '#ca8a04'],
            ['category' => 'Pastry', 'sku' => 'PST-002', 'name' => 'Cinnamon Roll', 'price' => 28000, 'stock' => 13, 'unit' => 'pcs', 'tag' => 'Sweet', 'color' => '#c2410c'],
            ['category' => 'Pastry', 'sku' => 'PST-003', 'name' => 'Smoked Beef Panini', 'price' => 42000, 'stock' => 11, 'unit' => 'pcs', 'tag' => 'Savory', 'color' => '#4d7c0f'],
        ];
    }
}
