<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\PageView;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrafficController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->string('period')->toString() ?: '7days';
        [$start, $end, $prevStart, $prevEnd] = $this->resolveDateRanges($period);

        // --- KPI summary ---
        $currentViews = PageView::human()->period($start, $end)->count();
        $currentVisitors = PageView::human()->period($start, $end)->distinct('visitor_id')->count('visitor_id');
        $currentSessions = PageView::human()->period($start, $end)->distinct('session_id')->count('session_id');

        $prevViews = PageView::human()->period($prevStart, $prevEnd)->count();
        $prevVisitors = PageView::human()->period($prevStart, $prevEnd)->distinct('visitor_id')->count('visitor_id');

        $viewsGrowth = $prevViews > 0 ? round((($currentViews - $prevViews) / $prevViews) * 100, 1) : 0;
        $visitorsGrowth = $prevVisitors > 0 ? round((($currentVisitors - $prevVisitors) / $prevVisitors) * 100, 1) : 0;

        $pagesPerSession = $currentSessions > 0 ? round($currentViews / $currentSessions, 1) : 0;

        // --- Daily trend ---
        $dailyTrend = PageView::human()
            ->period($start, $end)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // --- Top Pages ---
        $topPages = PageView::human()
            ->period($start, $end)
            ->select('path', 'page_title', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('path', 'page_title')
            ->orderByDesc('views')
            ->take(15)
            ->get()
            ->map(function ($item) use ($currentViews) {
                $item->percentage = $currentViews > 0 ? round(($item->views / $currentViews) * 100, 1) : 0;
                $item->resolved_title = $this->resolvePathTitle($item->path, $item->page_title);

                return $item;
            });

        // --- Traffic Sources / Referrers ---
        $topSources = PageView::human()
            ->period($start, $end)
            ->select('referer_domain', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('referer_domain')
            ->orderByDesc('views')
            ->take(10)
            ->get()
            ->map(function ($item) use ($currentViews) {
                $item->referer_domain = $item->referer_domain ?: 'Direct';
                $item->percentage = $currentViews > 0 ? round(($item->views / $currentViews) * 100, 1) : 0;

                return $item;
            });

        // --- Marketing & UTM Campaigns ---
        $utmCampaigns = PageView::human()
            ->period($start, $end)
            ->whereNotNull('utm_source')
            ->select('utm_source', 'utm_medium', 'utm_campaign', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_id) as visitors'))
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('views')
            ->take(10)
            ->get();

        // --- Devices & Browsers ---
        $devices = PageView::human()
            ->period($start, $end)
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->all();

        $browsers = PageView::human()
            ->period($start, $end)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        // --- Live / Recent Visitor Journeys ---
        $recentSessions = $this->getRecentVisitorJourneys($start, $end);

        return Inertia::render('Admin/Traffic/Index', [
            'period' => $period,
            'kpis' => [
                'totalViews' => $currentViews,
                'viewsGrowth' => $viewsGrowth,
                'uniqueVisitors' => $currentVisitors,
                'visitorsGrowth' => $visitorsGrowth,
                'totalSessions' => $currentSessions,
                'pagesPerSession' => $pagesPerSession,
            ],
            'dailyTrend' => $dailyTrend,
            'topPages' => $topPages,
            'topSources' => $topSources,
            'utmCampaigns' => $utmCampaigns,
            'devices' => [
                'desktop' => $devices['desktop'] ?? 0,
                'mobile' => $devices['mobile'] ?? 0,
                'tablet' => $devices['tablet'] ?? 0,
            ],
            'browsers' => $browsers,
            'recentSessions' => $recentSessions,
        ]);
    }

    private function resolveDateRanges(string $period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDay();
                $prevEnd = $end->copy()->subDay();
                break;

            case '30days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDays(30);
                $prevEnd = $start->copy()->subSecond();
                break;

            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfDay();
                $days = $start->diffInDays($end) + 1;
                $prevStart = $start->copy()->subMonths(1);
                $prevEnd = $prevStart->copy()->addDays($days)->endOfDay();
                break;

            case '7days':
            default:
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                $prevStart = $start->copy()->subDays(7);
                $prevEnd = $start->copy()->subSecond();
                break;
        }

        return [$start, $end, $prevStart, $prevEnd];
    }

    private function resolvePathTitle(string $path, ?string $fallback): string
    {
        if ($path === '/') return 'Acasă';
        if ($path === '/magazin') return 'Catalog Magazin Online';
        if ($path === '/magazin/cos') return 'Coș de cumpărături';
        if ($path === '/contact') return 'Pagina de Contact';
        if ($path === '/servicii') return 'Servicii Oferite';
        if ($path === '/cerere-deviz') return 'Cerere de deviz';

        if (preg_match('#^/magazin/([^/]+)$#', $path, $matches)) {
            $equipment = Equipment::where('slug', $matches[1])->first();
            if ($equipment) {
                return 'Produs: '.$equipment->name;
            }
        }

        if (preg_match('#^/blog/([^/]+)$#', $path, $matches)) {
            $post = Post::where('slug', $matches[1])->first();
            if ($post) {
                return 'Articol: '.$post->title;
            }
        }

        return $fallback ?: $path;
    }

    private function getRecentVisitorJourneys($start, $end): array
    {
        $recentViews = PageView::human()
            ->period($start, $end)
            ->whereNotNull('session_id')
            ->orderBy('created_at', 'desc')
            ->take(200)
            ->get();

        $grouped = $recentViews->groupBy('session_id');
        $sessions = [];

        foreach ($grouped->take(15) as $sessionId => $views) {
            $sorted = $views->sortBy('created_at')->values();
            $first = $sorted->first();
            $last = $sorted->last();

            $sessions[] = [
                'session_id' => $sessionId,
                'visitor_id' => $first->visitor_id,
                'ip_address' => $first->ip_address,
                'device_type' => $first->device_type,
                'browser' => $first->browser,
                'platform' => $first->platform,
                'referer_domain' => $first->referer_domain ?: 'Direct',
                'utm_source' => $first->utm_source,
                'utm_campaign' => $first->utm_campaign,
                'first_seen' => $first->created_at->format('H:i, d M'),
                'last_seen' => $last->created_at->format('H:i'),
                'page_count' => $sorted->count(),
                'landing_page' => $first->path,
                'exit_page' => $last->path,
                'pages' => $sorted->map(fn ($pv) => [
                    'path' => $pv->path,
                    'title' => $this->resolvePathTitle($pv->path, $pv->page_title),
                    'time' => $pv->created_at->format('H:i:s'),
                ])->all(),
            ];
        }

        return $sessions;
    }
}
