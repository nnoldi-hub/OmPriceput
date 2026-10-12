<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    public function test_settings_page_shows_defaults_when_nothing_stored(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Edit')
                ->where('settings.company_name', 'Om Priceput')
                ->where('settings.pricing_area_m2', '25')
                ->where('settings.pricing_perimeter_ml', '10')
            );
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAs($this->adminUser)->put(route('admin.settings.update'), [
            'company_name' => 'Noua Firma SRL',
            'company_email' => 'contact@noua-firma.ro',
            'company_phone' => '0722000000',
            'company_address' => 'Str. Test 1',
            'invoice_series' => 'NF',
            'vat_percentage' => 19,
        ])->assertRedirect();

        $this->assertEquals('Noua Firma SRL', Setting::get('company_name'));
        $this->assertEquals('NF', Setting::get('invoice_series'));
    }

    public function test_admin_can_update_measurement_pricing(): void
    {
        $this->actingAs($this->adminUser)->put(route('admin.settings.update'), [
            'company_name' => 'Om Priceput',
            'company_email' => 'contact@ompriceput.test',
            'company_phone' => '0700 000 000',
            'invoice_series' => 'OP',
            'vat_percentage' => 19,
            'pricing_area_m2' => 45.5,
            'pricing_perimeter_ml' => 12,
        ])->assertRedirect();

        $this->assertEquals('45.5', Setting::get('pricing_area_m2'));
        $this->assertEquals('12', Setting::get('pricing_perimeter_ml'));
    }
}
