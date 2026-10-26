<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Support\PageViewRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $request->user() || PageViewRules::isPrefetch($request)) {
            return $response;
        }

        $path = PageViewRules::normalizePath($request->path());

        if (PageViewRules::isIgnoredPath($path)) {
            return $response;
        }

        try {
            $visitorId = $request->cookie('op_vid');
            if (! $visitorId) {
                $visitorId = (string) Str::uuid();
                Cookie::queue('op_vid', $visitorId, 60 * 24 * 365);
            }

            if (! Cache::add("pv:{$visitorId}:{$path}", 1, 10)) {
                return $response;
            }

            $userAgent = (string) $request->header('User-Agent', '');
            $referer = $request->header('referer');
            $refererDomain = $this->extractRefererDomain($referer, $request->getHost());

            PageView::create([
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'visitor_id' => $visitorId,
                'user_id' => $request->user()?->id,
                'url' => Str::limit($request->fullUrl(), 2048, ''),
                'path' => Str::limit($path, 255, ''),
                'page_title' => $this->guessPageTitle($path),
                'method' => 'GET',
                'ip_address' => PageViewRules::anonymizeIp($request->ip()),
                'device_type' => PageViewRules::deviceType($userAgent),
                'browser' => PageViewRules::browser($userAgent),
                'platform' => PageViewRules::platform($userAgent),
                'user_agent' => Str::limit($userAgent, 1000, ''),
                'referer' => $referer ? Str::limit($referer, 1000, '') : null,
                'referer_domain' => $refererDomain,
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_content' => $request->query('utm_content'),
                'is_bot' => PageViewRules::isBot($userAgent),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Page view tracking failed.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function extractRefererDomain(?string $referer, string $appHost): ?string
    {
        if (! $referer) {
            return 'Direct';
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (! $host || strtolower($host) === strtolower($appHost)) {
            return 'Direct';
        }

        return strtolower($host);
    }

    private function guessPageTitle(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return 'Acasă';
        }
        if ($path === 'magazin') {
            return 'Magazin Online';
        }
        if ($path === 'magazin/cos') {
            return 'Coș de cumpărături';
        }
        if (Str::startsWith($path, 'magazin/comanda')) {
            return 'Confirmare Comandă';
        }
        if (Str::startsWith($path, 'magazin/')) {
            return 'Produs Magazin';
        }
        if ($path === 'servicii') {
            return 'Servicii';
        }
        if ($path === 'cerere-deviz') {
            return 'Cerere de deviz';
        }
        if ($path === 'contact') {
            return 'Contact';
        }
        if ($path === 'despre') {
            return 'Despre Noi';
        }
        if ($path === 'blog') {
            return 'Blog';
        }
        if (Str::startsWith($path, 'blog/')) {
            return 'Articol Blog';
        }
        if ($path === 'termeni') {
            return 'Termeni și Condiții';
        }
        if ($path === 'confidentialitate') {
            return 'Politica de Confidențialitate';
        }

        return Str::headline($path);
    }
}
