<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'customer' => 'Pelanggan',
        ], UserRole::options());
    }

    public function test_cashier_can_use_pos_but_cannot_manage_products_or_reports(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('pos.index'))->assertOk();
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
    }

    public function test_warehouse_can_manage_products_but_cannot_use_cashier_screen(): void
    {
        $warehouse = User::factory()->warehouse()->create();

        $this->actingAs($warehouse)->get(route('products.index'))->assertOk();
        $this->actingAs($warehouse)->get(route('pos.index'))->assertForbidden();
        $this->actingAs($warehouse)->get(route('reports.index'))->assertForbidden();
    }

    public function test_manager_and_owner_can_view_reports_without_cashier_access(): void
    {
        $manager = User::factory()->manager()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($manager)->get(route('dashboard.index'))->assertOk();
        $this->actingAs($manager)->get(route('reports.index'))->assertOk();
        $this->actingAs($manager)->get(route('sales.index'))->assertOk();
        $this->actingAs($manager)->get(route('pos.index'))->assertForbidden();

        $this->actingAs($owner)->get(route('dashboard.index'))->assertOk();
        $this->actingAs($owner)->get(route('reports.index'))->assertOk();
        $this->actingAs($owner)->get(route('sales.index'))->assertOk();
        $this->actingAs($owner)->get(route('products.index'))->assertForbidden();
    }

    public function test_admin_and_super_admin_can_access_core_system_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        foreach ([$admin, $superAdmin] as $user) {
            $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
            $this->actingAs($user)->get(route('pos.index'))->assertOk();
            $this->actingAs($user)->get(route('products.index'))->assertOk();
            $this->actingAs($user)->get(route('sales.index'))->assertOk();
            $this->actingAs($user)->get(route('reports.index'))->assertOk();
        }
    }

    public function test_super_admin_can_manage_user_page_access_from_database(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($superAdmin)
            ->patch(route('access-control.update', $cashier), [
                'permissions' => ['page.reports', 'page.reports_export'],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $cashier->id,
            'permission' => 'page.reports',
            'allowed' => true,
        ]);

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $cashier->id,
            'permission' => 'page.pos',
            'allowed' => false,
        ]);

        $cashier->refresh();

        $this->actingAs($cashier)->get(route('reports.index'))->assertOk();
        $this->actingAs($cashier)->get(route('pos.index'))->assertForbidden();
    }

    public function test_admin_cannot_manage_page_access(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($admin)
            ->get(route('access-control.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('access-control.update', $cashier), [
                'permissions' => ['page.dashboard', 'page.pos', 'page.products', 'page.reports'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_permissions', [
            'user_id' => $cashier->id,
        ]);
    }

    public function test_super_admin_page_access_is_locked(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $target = User::factory()->superAdmin()->create();

        $this->actingAs($actor)
            ->patch(route('access-control.update', $target), [
                'permissions' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('user_permissions', [
            'user_id' => $target->id,
        ]);

        $this->assertTrue($target->refresh()->hasPermission('page.pos'));
        $this->assertTrue($target->hasPermission('unknown.future_permission'));
    }

    public function test_access_control_does_not_edit_other_user_profile_photo_or_password(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $cashier = User::factory()->cashier()->create([
            'name' => 'Kasir Lama',
            'username' => 'kasir_lama',
            'email' => 'kasir-lama@example.test',
            'password' => Hash::make('old-password-123'),
            'avatar_path' => 'avatars/existing.jpg',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('access-control.update', $cashier), [
                'name' => 'Kasir Baru',
                'username' => 'kasir_baru',
                'email' => 'kasir-baru@example.test',
                'role' => UserRole::Manager->value,
                'avatar_crop' => 'data:image/jpeg;base64,' . base64_encode('cropped-avatar'),
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
                'permissions' => ['page.dashboard', 'page.reports'],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $cashier->refresh();

        $this->assertSame('Kasir Lama', $cashier->name);
        $this->assertSame('kasir_lama', $cashier->username);
        $this->assertSame('kasir-lama@example.test', $cashier->email);
        $this->assertSame('avatars/existing.jpg', $cashier->avatar_path);
        $this->assertTrue(Hash::check('old-password-123', $cashier->password));
        $this->assertSame(UserRole::Manager, $cashier->role);

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $cashier->id,
            'permission' => 'page.reports',
            'allowed' => true,
        ]);
    }

    public function test_authenticated_user_can_update_own_profile_avatar_and_password(): void
    {
        Storage::fake('public');

        $user = User::factory()->superAdmin()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $avatar = 'data:image/jpeg;base64,' . base64_encode('my-face-photo');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Super Admin Baru',
                'avatar_crop' => $avatar,
                'current_password' => 'old-password-123',
                'password' => 'new-password-456',
                'password_confirmation' => 'new-password-456',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('Super Admin Baru', $user->name);
        $this->assertTrue(Hash::check('new-password-456', $user->password));
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_report_exports_are_available_to_report_roles(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('reports.print'))
            ->assertOk()
            ->assertSee('Laporan POS Cafe');

        $this->actingAs($manager)
            ->get(route('reports.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($manager)
            ->get(route('reports.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
    }

    public function test_cashier_cannot_access_report_exports(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('reports.print'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.pdf'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.excel'))->assertForbidden();
        $this->actingAs($cashier)->get(route('dashboard.index'))->assertForbidden();
    }

    public function test_sales_exports_are_available_to_transaction_report_roles(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('sales.print'))
            ->assertOk()
            ->assertSee('Order / Transaksi POS Cafe');

        $this->actingAs($manager)
            ->get(route('sales.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($manager)
            ->get(route('sales.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
    }

    public function test_cashier_cannot_access_sales_exports(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('sales.print'))->assertForbidden();
        $this->actingAs($cashier)->get(route('sales.pdf'))->assertForbidden();
        $this->actingAs($cashier)->get(route('sales.excel'))->assertForbidden();
    }

    public function test_customer_can_view_menu_only(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('customer.menu'))->assertOk();
        $this->actingAs($customer)->get(route('pos.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('products.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.index'))->assertForbidden();
    }
}
