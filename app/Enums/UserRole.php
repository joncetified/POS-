<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Cashier = 'cashier';
    case Warehouse = 'warehouse';
    case Manager = 'manager';
    case Owner = 'owner';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Cashier => 'Cashier / Kasir',
            self::Warehouse => 'Warehouse / Gudang',
            self::Manager => 'Manager / Supervisor',
            self::Owner => 'Owner',
            self::Customer => 'Pelanggan',
        };
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                'page.dashboard',
                'page.pos',
                'page.qr_tables',
                'page.customer_menu',
                'page.products',
                'page.orders',
                'page.sales',
                'page.sales_export',
                'page.reports',
                'page.reports_export',
                'page.operations',
                'page.settings',
                'features.access_all',
                'branches.access_all',
                'admins.manage',
                'system.settings',
                'reports.view_all',
            ],
            self::Admin => [
                'page.dashboard',
                'page.pos',
                'page.qr_tables',
                'page.products',
                'page.orders',
                'page.sales',
                'page.sales_export',
                'page.reports',
                'page.reports_export',
                'page.operations',
                'products.manage',
                'stock.manage',
                'transactions.manage',
                'reports.view_store',
                'cashiers.manage',
            ],
            self::Cashier => [
                'page.pos',
                'page.qr_tables',
                'page.orders',
                'items.scan',
                'transactions.create',
                'receipts.print',
                'payments.process',
            ],
            self::Warehouse => [
                'page.products',
                'stock.in',
                'stock.out',
                'inventory.manage',
                'stock.transfer',
                'page.operations',
            ],
            self::Manager => [
                'page.dashboard',
                'page.sales',
                'page.sales_export',
                'page.reports',
                'page.reports_export',
                'page.operations',
                'reports.view_store',
                'discounts.approve',
                'cashiers.monitor',
                'store.performance.view',
            ],
            self::Owner => [
                'page.dashboard',
                'page.sales',
                'page.sales_export',
                'page.reports',
                'page.reports_export',
                'page.operations',
                'dashboard.view',
                'profits.view',
                'branches.monitor',
            ],
            self::Customer => [
                'page.customer_menu',
                'orders.place',
                'menu.view',
            ],
        };
    }

    public function can(string $permission): bool
    {
        return $this === self::SuperAdmin || in_array($permission, $this->permissions(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
