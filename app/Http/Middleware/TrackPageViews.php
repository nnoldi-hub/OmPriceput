<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET')) {
            return $response;
        }

        $path = $request->path();

        if ($this->shouldIgnorePath($path)) {
            return $response;
        }

        try {
            $visitorId = $request->cookie('op_vid');
            if (! $visitorId) {
                $visitorId = (string) Str::uuid();
                Cookie::queue('op_vid', $visitorId, 60 * 24 * 365);
            }

            $userAgent = $request->header('User-Agent', '');
            $referer = $request->header('referer');
            $refererDomain = $this->extractRefererDomain($referer, $request->getHost());

            $isBot = $this->detectBot($userAgent);
            $deviceType = $this->detectDevice($userAgent);
            $browser = $this->detectBrowser($userAgent);
            $platform = $this->detectPlatform($userAgent);

            PageView::create([
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'visitor_id' => $visitorId,
                'user_id' => $request->user()?->id,
                'url' => Str::limit($request->fullUrl(), 2048, ''),
                'path' => Str::limit('/'.ltrim($path, '/'), 255, ''),
                'page_title' => $this->guessPageTitle($path),
                'method' => 'GET',
                'ip_address' => $request->ip(),
                'device_type' => $deviceType,
                'browser' => $browser,
                'platform' => $platform,
                'user_agent' => Str::limit($userAgent, 1000, ''),
                'referer' => $referer ? Str::limit($referer, 1000, '') : null,
                'referer_domain' => $refererDomain,
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_content' => $request->query('utm_content'),
                'is_bot' => $isBot,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore logging failures to never break user requests
        }

        return $response;
    }

    private function shouldIgnorePath(string $path): bool
    {
        $ignoredPrefixes = ['admin', 'client', 'api', 'build', 'storage', 'vendor', '_debugbar', 'up', 'horizon', 'telescope'];

        foreach ($ignoredPrefixes as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        if (Str::endsWith($path, ['.js', '.css', '.ico', '.png', '.jpg', '.jpeg', '.svg', '.woff', '.woff2', '.map', '.json'])) {
            return true;
        }

        return false;
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

    private function detectBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $botPattern = '/(bot|crawler|spider|slurp|mediapartners|lighthouse|ptst|pingdom|googlebot|bingbot|yandexbot|facebookexternalhit|whatsapp|twitterbot|curl|wget|python|php|headful)/i';

        return (bool) preg_match($botPattern, $userAgent);
    }

    private function detectDevice(string $userAgent): string
    {
        if (preg_match('/(ipad|tablet|playbook|kindle|nexus 7|nexus 10)/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|opera mini|windows phone)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function detectBrowser(string $userAgent): string
    {
        if (preg_match('/Edg/i', $userAgent)) return 'Edge';
        if (preg_match('/OPR|Opera/i', $userAgent)) return 'Opera';
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/MSIE|Trident/i', $userAgent)) return 'IE';

        return 'Other';
    }

    private function detectPlatform(string $userAgent): string
    {
        if (preg_match('/Windows/i', $userAgent)) return 'Windows';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) return 'iOS';
        if (preg_match('/Macintosh|Mac OS/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';

        return 'Other';
    }

    private function guessPageTitle(string $path): string
    {
        $path = trim($path, '/');
        if ($path === '') return 'Acasă';
        if ($path === 'magazin') return 'Magazin Online';
        if ($path === 'magazin/cos') return 'Coș de cumpărături';
        if (Str::startsWith($path, 'magazin/comanda')) return 'Confirmare Comandă';
        if (Str::startsWith($path, 'magazin/')) return 'Produs Magazin';
        if ($path === 'servicii') return 'Servicii';
        if ($path === 'cerere-deviz') return 'Cerere de deviz';
        if ($path === 'contact') return 'Contact';
        if ($path === 'despre') return 'Despre Noi';
        if ($path === 'blog') return 'Blog';
        if (Str::startsWith($path, 'blog/')) return 'Articol Blog';
        if ($path === 'termeni') return 'Termeni și Condiții';
        if ($path === 'confidentialitate') return 'Politica de Confidențialitate';

        return Str::headline($path);
    }
}
