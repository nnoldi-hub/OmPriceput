<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageViewRules
{
    public const EXACT_IGNORED_PATHS = [
        '/login',
        '/logout',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/confirm-password',
        '/email/verify',
        '/password',
        '/cerere-deviz/zile',
        '/cerere-deviz/sloturi',
    ];

    public const IGNORED_PREFIXES = [
        '/admin',
        '/client',
        '/api',
        '/build',
        '/storage',
        '/vendor',
        '/_debugbar',
        '/horizon',
        '/telescope',
        '/c',
        '/recenzie',
    ];

    public const IGNORED_EXTENSIONS = [
        '.js',
        '.css',
        '.ico',
        '.png',
        '.jpg',
        '.jpeg',
        '.webp',
        '.gif',
        '.svg',
        '.woff',
        '.woff2',
        '.ttf',
        '.map',
        '.json',
        '.xml',
        '.txt',
    ];

    private const BOT_PATTERN = '/(bot|crawl|spider|slurp|headless|preview)/i';

    private const BOT_TOKENS = [
        'whatsapp',
        'facebookexternalhit',
        'telegram',
        'slack',
        'lighthouse',
        'chrome-lighthouse',
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'windowspowershell',
        'phantomjs',
        'selenium',
        'puppeteer',
        'playwright',
        'pingdom',
        'ptst',
        'mediapartners',
        'pagespeed',
        'gtmetrix',
        'ahrefs',
        'semrush',
        'mj12bot',
        'dotbot',
        'petalbot',
        'bytespider',
        'uptimerobot',
        'statuscake',
    ];

    public static function normalizePath(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    public static function isIgnoredPath(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return true;
        }

        $path = self::normalizePath($path);

        if (in_array($path, self::EXACT_IGNORED_PATHS, true)) {
            return true;
        }

        foreach (self::IGNORED_PREFIXES as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        $lowerPath = Str::lower($path);

        foreach (self::IGNORED_EXTENSIONS as $extension) {
            if (Str::endsWith($lowerPath, $extension)) {
                return true;
            }
        }

        return false;
    }

    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        if (preg_match(self::BOT_PATTERN, str_ireplace('cubot', '', $userAgent)) === 1) {
            return true;
        }

        $userAgent = Str::lower($userAgent);

        foreach (self::BOT_TOKENS as $token) {
            if (str_contains($userAgent, $token)) {
                return true;
            }
        }

        return false;
    }

    public static function deviceType(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        if ($userAgent === '') {
            return 'desktop';
        }

        if (preg_match('/(ipad|tablet|playbook|kindle|silk|nexus 7|nexus 10|sm-t\d|pixel c|lenovo tab)/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/macintosh/i', $userAgent) && preg_match('/mobile/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/android/i', $userAgent) && ! preg_match('/mobile/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(iphone|ipod|android.*mobile|mobile|blackberry|opera mini|windows phone|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function browser(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        if (preg_match('/Edg|Edge/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/OPR|Opera|OPiOS/i', $userAgent)) {
            return 'Opera';
        }
        if (preg_match('/SamsungBrowser/i', $userAgent)) {
            return 'Samsung Internet';
        }
        if (preg_match('/Chrome|CriOS/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Safari/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Firefox|FxiOS/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            return 'IE';
        }

        return 'Other';
    }

    public static function platform(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/CrOS/i', $userAgent)) {
            return 'ChromeOS';
        }
        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Other';
    }

    public static function isPrefetch(Request $request): bool
    {
        foreach (['Purpose', 'Sec-Purpose', 'X-Purpose', 'X-Moz'] as $header) {
            $value = Str::lower((string) $request->headers->get($header, ''));

            if ($value !== '' && (str_contains($value, 'prefetch') || str_contains($value, 'prerender') || str_contains($value, 'preview'))) {
                return true;
            }
        }

        return false;
    }

    public static function anonymizeIp(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $ip = trim($ip);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            if ($packed === false) {
                return null;
            }

            $anonymous = inet_ntop(substr($packed, 0, 6).str_repeat("\0", 10));

            return $anonymous === false ? null : $anonymous;
        }

        return null;
    }
}
