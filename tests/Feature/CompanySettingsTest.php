<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\CafeCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_website_settings_from_database(): void
    {
        Storage::fake('public');

        $superAdmin = User::factory()->superAdmin()->create();
        $logo = UploadedFile::fake()->image('logo.png', 160, 160);
        $paymentBarcode = UploadedFile::fake()->image('barcode-payment.png', 420, 420);

        $this->actingAs($superAdmin)
            ->put(route('settings.update'), [
                'company_name' => 'Cafe Serius Baru',
                'manager_name' => 'Nadia Manager',
                'contact_email' => 'halo@cafeserius.test',
                'contact_phone' => '021-555-0101',
                'contact_whatsapp' => '6281234567890',
                'address' => 'Jl. Testing POS No. 10',
                'logo' => $logo,
                'payment_barcode' => $paymentBarcode,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $settings = CompanySetting::query()->firstOrFail();

        $this->assertSame('Cafe Serius Baru', $settings->company_name);
        $this->assertSame('Nadia Manager', $settings->manager_name);
        $this->assertSame('halo@cafeserius.test', $settings->contact_email);
        $this->assertSame('021-555-0101', $settings->contact_phone);
        $this->assertSame('6281234567890', $settings->contact_whatsapp);
        $this->assertSame('Jl. Testing POS No. 10', $settings->address);
        $this->assertNotNull($settings->logo_path);
        $this->assertNotNull($settings->payment_barcode_path);
        Storage::disk('public')->assertExists($settings->logo_path);
        Storage::disk('public')->assertExists($settings->payment_barcode_path);

        $store = CafeCatalog::store();

        $this->assertSame('Cafe Serius Baru', $store['name']);
        $this->assertSame('Jl. Testing POS No. 10', $store['address']);
        $this->assertSame('Nadia Manager', $store['manager']);
        $this->assertStringStartsWith('/storage/company-logos/', $store['logo_url']);
        $this->assertStringStartsWith('/storage/payment-barcodes/', $store['payment_barcode_url']);

        $this->actingAs($superAdmin)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Cafe Serius Baru');
    }

    public function test_admin_without_settings_permission_cannot_manage_website_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => 'Tidak Boleh',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('company_settings', [
            'company_name' => 'Tidak Boleh',
        ]);
    }
}
