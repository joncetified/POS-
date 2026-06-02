<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CafeCatalog
{
    /**
     * @return array<string, string|null>
     */
    public static function defaultStore(): array
    {
        $name = config('store.name') ?: "Purr' Coffee";

        return [
            'name' => $name,
            'cashier' => config('store.cashier', 'Barista 01'),
            'address' => config('store.address', 'Jl. Kopi Nusantara No. 8, Jakarta'),
            'manager' => config('store.manager', 'Manager Operasional'),
            'contact_email' => config('store.contact_email'),
            'contact_phone' => config('store.contact_phone'),
            'contact_whatsapp' => config('store.contact_whatsapp'),
            'logo_path' => null,
            'logo_url' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function store(): array
    {
        $defaults = self::defaultStore();

        if (! Schema::hasTable('company_settings')) {
            return $defaults;
        }

        $settings = CompanySetting::current($defaults);
        $logoPath = $settings->logo_path;

        return [
            'name' => $settings->company_name ?: $defaults['name'],
            'cashier' => $defaults['cashier'],
            'address' => $settings->address ?: $defaults['address'],
            'manager' => $settings->manager_name ?: $defaults['manager'],
            'contact_email' => $settings->contact_email,
            'contact_phone' => $settings->contact_phone,
            'contact_whatsapp' => $settings->contact_whatsapp,
            'logo_path' => $logoPath,
            'logo_url' => $logoPath ? asset('storage/' . $logoPath) : null,
        ];
    }

    public static function tables(): array
    {
        $count = (int) config('store.table_count', 20);
        $count = max(1, min($count, 200));

        return range(1, $count);
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
