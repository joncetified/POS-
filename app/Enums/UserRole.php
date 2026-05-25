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

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Cashier => 'Cashier / Kasir',
            self::Warehouse => 'Warehouse / Gudang',
            self::Manager => 'Manager / Supervisor',
            self::Owner => 'Owner',
        };
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                'features.access_all',
                'branches.access_all',
                'admins.manage',
                'system.settings',
                'reports.view_all',
            ],
            self::Admin => [
                'products.manage',
                'stock.manage',
                'transactions.manage',
                'reports.view_store',
                'cashiers.manage',
            ],
            self::Cashier => [
                'items.scan',
                'transactions.create',
                'receipts.print',
                'payments.process',
            ],
            self::Warehouse => [
                'stock.in',
                'stock.out',
                'inventory.manage',
                'stock.transfer',
            ],
            self::Manager => [
                'reports.view_store',
                'discounts.approve',
                'cashiers.monitor',
                'store.performance.view',
            ],
            self::Owner => [
                'dashboard.view',
                'profits.view',
                'branches.monitor',
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
