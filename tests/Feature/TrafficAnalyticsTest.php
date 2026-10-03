<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create();
    }

    public function test_public_visit_is_tracked_and_visitor_cookie_is_set(): void
    {
        $response = $this->get('/servicii?utm_source=google&utm_medium=cpc&utm_campaign=promo2026');

        $response->assertOk();
        $response->assertCookie('op_vid');

        $this->assertDatabaseHas('page_views', [
            'path' => '/servicii',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'promo2026',
            'is_bot' => false,
        ]);
    }

    public function test_bot_and_admin_requests_are_filtered_or_marked(): void
    {
        // Bot request
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get('/servicii');

        $this->assertDatabaseHas('page_views', [
            'path' => '/servicii',
            'is_bot' => true,
        ]);

        // Admin request - should be ignored completely
        $this->actingAs($this->adminUser)->get('/admin/trafic');
        $this->assertDatabaseMissing('page_views', [
            'path' => '/admin/trafic',
        ]);
    }

    public function test_admin_can_view_traffic_analytics_page(): void
    {
        // Create sample page views
        PageView::create([
            'session_id' => 'sess_1',
            'visitor_id' => 'vid_1',
            'url' => 'http://localhost/magazin',
            'path' => '/magazin',
            'page_title' => 'Magazin Online',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'referer_domain' => 'google.com',
            'utm_source' => 'google',
            'utm_medium' => 'organic',
            'is_bot' => false,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.traffic.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Traffic/Index')
            ->has('kpis')
            ->has('dailyTrend')
            ->has('topPages')
            ->has('recentSessions')
        );
    }

    public function test_non_admin_cannot_access_traffic_analytics(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.traffic.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_save_analytics_and_pixel_ids(): void
    {
        $this->actingAs($this->adminUser)->put(route('admin.settings.update'), [
            'company_name' => 'Omul Potrivit',
            'company_email' => 'contact@omulpotrivit.test',
            'company_phone' => '0700000000',
            'company_address' => 'Str. Test 1',
            'company_hours' => 'Luni - Vineri, 09:00 - 18:00',
            'invoice_series' => 'OP',
            'vat_percentage' => 19,
            'minimum_profit_margin' => 20,
            'google_analytics_id' => 'G-ABC1234567',
            'google_tag_manager_id' => 'GTM-XYZ9876',
            'meta_pixel_id' => '123456789012345',
        ])->assertRedirect();

        $this->assertEquals('G-ABC1234567', Setting::get('google_analytics_id'));
        $this->assertEquals('GTM-XYZ9876', Setting::get('google_tag_manager_id'));
        $this->assertEquals('123456789012345', Setting::get('meta_pixel_id'));
    }
}
