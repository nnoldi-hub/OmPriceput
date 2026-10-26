<?php

namespace Tests\Feature;

use App\Models\Client;
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
            ->has('funnel')
            ->has('dailyTrend')
            ->has('topPages')
            ->has('recentSessions')
        );
    }

    public function test_traffic_dashboard_counts_only_human_traffic_and_matches_quote_funnel(): void
    {
        PageView::create([
            'session_id' => 'sess_human',
            'visitor_id' => 'vid_human',
            'url' => 'http://localhost/servicii',
            'path' => '/servicii',
            'method' => 'GET',
            'is_bot' => false,
        ]);

        PageView::create([
            'session_id' => 'sess_quote',
            'visitor_id' => 'vid_quote',
            'url' => 'http://localhost/cerere-deviz',
            'path' => '/cerere-deviz',
            'method' => 'GET',
            'is_bot' => false,
        ]);

        PageView::create([
            'session_id' => 'sess_bot',
            'visitor_id' => 'vid_bot',
            'url' => 'http://localhost/servicii',
            'path' => '/servicii',
            'method' => 'GET',
            'is_bot' => true,
        ]);

        PageView::create([
            'session_id' => 'sess_admin',
            'visitor_id' => 'vid_admin',
            'user_id' => $this->adminUser->id,
            'url' => 'http://localhost/servicii',
            'path' => '/servicii',
            'method' => 'GET',
            'is_bot' => false,
        ]);

        PageView::create([
            'session_id' => 'sess_calendar',
            'visitor_id' => 'vid_calendar',
            'url' => 'http://localhost/cerere-deviz/zile',
            'path' => '/cerere-deviz/zile',
            'method' => 'GET',
            'is_bot' => false,
        ]);

        PageView::create([
            'session_id' => 'sess_post',
            'visitor_id' => 'vid_post',
            'url' => 'http://localhost/cerere-deviz',
            'path' => '/cerere-deviz',
            'method' => 'POST',
            'is_bot' => false,
        ]);

        Client::create([
            'name' => 'Client Test',
            'phone' => '0700000000',
            'source' => 'web',
            'status' => 'lead',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.traffic.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.totalViews', 2)
            ->where('kpis.uniqueVisitors', 2)
            ->where('kpis.totalSessions', 2)
            ->where('funnel.quoteViews', 1)
            ->where('funnel.quoteVisitors', 1)
            ->where('funnel.leads', 1)
            ->where('funnel.visitorsToQuote', 50)
            ->where('funnel.quoteToLead', 100)
            ->where('funnel.visitorsToLead', 50)
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
            'company_name' => 'Om Priceput',
            'company_email' => 'contact@ompriceput.test',
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
