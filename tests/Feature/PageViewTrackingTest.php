<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_json_routes_are_not_tracked_and_keep_their_names(): void
    {
        $this->assertSame('/cerere-deviz/zile', route('public.quote.dates', [], false));
        $this->assertSame('/cerere-deviz/sloturi', route('public.quote.slots', [], false));

        $this->get('/cerere-deviz/zile')->assertOk();

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_quote_page_is_still_tracked(): void
    {
        $this->get('/cerere-deviz')->assertOk();

        $this->assertDatabaseHas('page_views', [
            'path' => '/cerere-deviz',
            'is_bot' => false,
        ]);
    }

    public function test_device_detection_for_common_human_user_agents(): void
    {
        $cases = [
            [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
                'mobile',
                'Safari',
                'iOS',
            ],
            [
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
                'mobile',
                'Chrome',
                'Android',
            ],
            [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'desktop',
                'Chrome',
                'Windows',
            ],
            [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
                'tablet',
                'Safari',
                'macOS',
            ],
            [
                'Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'tablet',
                'Chrome',
                'Android',
            ],
            [
                'Mozilla/5.0 (Linux; Android 12; CUBOT X30) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
                'mobile',
                'Chrome',
                'Android',
            ],
        ];

        foreach ($cases as [$userAgent, $device, $browser, $platform]) {
            $this->withHeaders(['User-Agent' => $userAgent])->get('/servicii')->assertOk();

            $this->assertDatabaseHas('page_views', [
                'user_agent' => $userAgent,
                'device_type' => $device,
                'browser' => $browser,
                'platform' => $platform,
                'is_bot' => false,
            ]);
        }
    }

    public function test_known_bots_and_tools_are_marked_as_bots(): void
    {
        $userAgents = [
            'WhatsApp/2.24.1.79 A',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'TelegramBot (like TwitterBot)',
            'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Chrome-Lighthouse',
            'curl/8.4.0',
            'python-requests/2.31.0',
            'Go-http-client/2.0',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/124.0.0.0 Safari/537.36',
        ];

        foreach ($userAgents as $userAgent) {
            $this->withHeaders(['User-Agent' => $userAgent])->get('/servicii')->assertOk();

            $this->assertDatabaseHas('page_views', [
                'user_agent' => $userAgent,
                'is_bot' => true,
            ]);
        }

        $this->assertDatabaseCount('page_views', count($userAgents));
    }

    public function test_authenticated_users_are_not_tracked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_head_requests_are_not_tracked(): void
    {
        $this->head('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_prefetch_and_prerender_requests_are_not_tracked(): void
    {
        $this->withHeaders(['Purpose' => 'prefetch'])->get('/servicii')->assertOk();
        $this->withHeaders(['Sec-Purpose' => 'prefetch;prerender'])->get('/servicii')->assertOk();
        $this->withHeaders(['X-Purpose' => 'preview'])->get('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 0);
    }

    public function test_repeated_views_are_deduplicated_for_ten_seconds(): void
    {
        $visitorId = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

        $this->withCookie('op_vid', $visitorId)->get('/servicii')->assertOk();
        $this->withCookie('op_vid', $visitorId)->get('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 1);

        $this->travel(9)->seconds();
        $this->withCookie('op_vid', $visitorId)->get('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 1);

        $this->travel(2)->seconds();
        $this->withCookie('op_vid', $visitorId)->get('/servicii')->assertOk();

        $this->assertDatabaseCount('page_views', 2);
    }

    public function test_missing_user_agent_is_handled_without_error(): void
    {
        $this->withHeaders(['User-Agent' => ''])->get('/servicii')->assertOk();

        $this->assertDatabaseHas('page_views', [
            'path' => '/servicii',
            'is_bot' => true,
        ]);
    }

    public function test_new_ips_are_anonymized_before_storage(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.123'])->get('/servicii')->assertOk();

        $this->assertDatabaseHas('page_views', [
            'path' => '/servicii',
            'ip_address' => '192.168.1.0',
        ]);
    }

    public function test_new_ipv6_addresses_keep_only_the_first_48_bits(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:85a3:1234:5678:9abc:def0:1234'])
            ->get('/despre')
            ->assertOk();

        $this->assertDatabaseHas('page_views', [
            'path' => '/despre',
            'ip_address' => '2001:db8:85a3::',
        ]);
    }

    public function test_human_scope_excludes_bots_users_internal_paths_and_non_get_requests(): void
    {
        $user = User::factory()->create();

        $rows = [
            ['path' => '/servicii', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/blog/articol', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/recenzii', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/bot', 'is_bot' => true, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/despre', 'is_bot' => false, 'user_id' => $user->id, 'method' => 'GET'],
            ['path' => '/admin/trafic', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/cerere-deviz/zile', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/c/token', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/build/app.js', 'is_bot' => false, 'user_id' => null, 'method' => 'GET'],
            ['path' => '/contact', 'is_bot' => false, 'user_id' => null, 'method' => 'POST'],
        ];

        foreach ($rows as $row) {
            PageView::create($row + [
                'visitor_id' => 'visitor',
                'url' => 'http://localhost'.$row['path'],
            ]);
        }

        $this->assertSame(
            ['/servicii', '/blog/articol', '/recenzii'],
            PageView::human()->orderBy('id')->pluck('path')->all(),
        );
    }
}
