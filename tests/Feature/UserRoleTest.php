<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_role_is_cast_and_exposes_permissions(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame('Admin', $user->roleLabel());
        $this->assertTrue($user->hasPermission('products.manage'));
        $this->assertFalse($user->hasPermission('system.settings'));
    }

    public function test_super_admin_can_access_every_permission(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->hasPermission('system.settings'));
        $this->assertTrue($user->hasPermission('products.manage'));
        $this->assertTrue($user->hasPermission('unknown.future_permission'));
    }

    public function test_role_options_include_requested_roles(): void
    {
        $this->assertSame([
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'cashier' => 'Cashier / Kasir',
            'warehouse' => 'Warehouse / Gudang',
            'manager' => 'Manager / Supervisor',
            'owner' => 'Owner',
        ], UserRole::options());
    }
}
