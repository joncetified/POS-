<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$app = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
$app->make(Kernel::class)->bootstrap();

$now = now();

$categories = [
    ['name' => 'Espresso Bar', 'slug' => 'espresso-bar', 'sort_order' => 1],
    ['name' => 'Manual Brew', 'slug' => 'manual-brew', 'sort_order' => 2],
    ['name' => 'Milk Coffee', 'slug' => 'milk-coffee', 'sort_order' => 3],
    ['name' => 'Non Coffee', 'slug' => 'non-coffee', 'sort_order' => 4],
    ['name' => 'Pastry', 'slug' => 'pastry', 'sort_order' => 5],
];

DB::transaction(function () use ($categories, $now): void {
    foreach ($categories as $category) {
        DB::table('categories')->updateOrInsert(
            ['slug' => $category['slug']],
            [
                'name' => $category['name'],
                'sort_order' => $category['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    $categoryIds = DB::table('categories')->pluck('id', 'slug');

    $products = [
        ['category' => 'espresso-bar', 'sku' => 'ESP-001', 'name' => 'Espresso Single', 'price' => 18000, 'stock' => 42, 'unit' => 'cup', 'tag' => 'Classic', 'color' => '#7c2d12'],
        ['category' => 'espresso-bar', 'sku' => 'ESP-002', 'name' => 'Americano Hot', 'price' => 24000, 'stock' => 38, 'unit' => 'cup', 'tag' => 'Black', 'color' => '#334155'],
        ['category' => 'manual-brew', 'sku' => 'BRW-001', 'name' => 'V60 Flores Bajawa', 'price' => 36000, 'stock' => 18, 'unit' => 'cup', 'tag' => 'Filter', 'color' => '#0f766e'],
        ['category' => 'manual-brew', 'sku' => 'BRW-002', 'name' => 'Japanese Iced Coffee', 'price' => 38000, 'stock' => 21, 'unit' => 'cup', 'tag' => 'Cold', 'color' => '#2563eb'],
        ['category' => 'milk-coffee', 'sku' => 'MLK-001', 'name' => 'Cappuccino', 'price' => 32000, 'stock' => 34, 'unit' => 'cup', 'tag' => 'Foamy', 'color' => '#a16207'],
        ['category' => 'milk-coffee', 'sku' => 'MLK-002', 'name' => 'Kopi Susu Gula Aren', 'price' => 28000, 'stock' => 55, 'unit' => 'cup', 'tag' => 'Favorit', 'color' => '#be123c'],
        ['category' => 'milk-coffee', 'sku' => 'MLK-003', 'name' => 'Iced Cafe Latte', 'price' => 33000, 'stock' => 29, 'unit' => 'cup', 'tag' => 'Iced', 'color' => '#0891b2'],
        ['category' => 'non-coffee', 'sku' => 'NON-001', 'name' => 'Matcha Latte', 'price' => 34000, 'stock' => 24, 'unit' => 'cup', 'tag' => 'Tea', 'color' => '#15803d'],
        ['category' => 'non-coffee', 'sku' => 'NON-002', 'name' => 'Chocolate Signature', 'price' => 32000, 'stock' => 27, 'unit' => 'cup', 'tag' => 'Cocoa', 'color' => '#78350f'],
        ['category' => 'pastry', 'sku' => 'PST-001', 'name' => 'Butter Croissant', 'price' => 26000, 'stock' => 16, 'unit' => 'pcs', 'tag' => 'Fresh', 'color' => '#ca8a04'],
    ];

    foreach ($products as $product) {
        DB::table('products')->updateOrInsert(
            ['sku' => $product['sku']],
            [
                'category_id' => $categoryIds[$product['category']],
                'name' => $product['name'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'unit' => $product['unit'],
                'tag' => $product['tag'],
                'color' => $product['color'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    $users = [
        ['username' => 'superadmin', 'email' => 'superadmin@pos.test', 'role' => 'super_admin'],
        ['username' => 'admin', 'email' => 'admin@pos.test', 'role' => 'admin'],
        ['username' => 'kasir', 'email' => 'kasir@pos.test', 'role' => 'cashier'],
        ['username' => 'gudang', 'email' => 'gudang@pos.test', 'role' => 'warehouse'],
        ['username' => 'manager', 'email' => 'manager@pos.test', 'role' => 'manager'],
        ['username' => 'owner', 'email' => 'owner@pos.test', 'role' => 'owner'],
        ['username' => 'pelanggan', 'email' => 'pelanggan@pos.test', 'role' => 'customer'],
    ];

    foreach ($users as $user) {
        DB::table('users')->updateOrInsert(
            ['email' => $user['email']],
            [
                'name' => $user['username'],
                'username' => $user['username'],
                'email_verified_at' => $now,
                'role' => $user['role'],
                'password' => Hash::make('password123'),
                'email_verification_code' => null,
                'email_verification_expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
});

echo "Selesai: 5 kategori, 10 produk, dan 7 akun role sudah siap di database.\n";
