<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\SitePackage;
use App\Models\Stat;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $packages = SitePackage::where('active', true)->orderBy('sort_order')->orderBy('name')->get();

        app(Seo::class)->jsonLd([$this->localBusinessSchema()]);

        return Inertia::render('Public/Home', [
            'page' => Page::published()->with('sections')->where('slug', 'acasa')->first(),
            'stats' => Stat::all(),
            'packages' => $packages->isNotEmpty() ? $packages : config('packages.tiers'),
            'latestPosts' => Post::published()->latest('published_at')->take(3)->get([
                'title', 'slug', 'excerpt', 'published_at',
            ]),
        ]);
    }

    private function localBusinessSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HomeAndConstructionBusiness',
            'name' => Setting::get('company_name') ?: config('app.name'),
            'url' => route('public.home'),
            'image' => asset('branding/logo-trim.png'),
        ];

        if ($phone = Setting::get('company_phone')) {
            $schema['telephone'] = $phone;
        }

        if ($address = Setting::get('company_address')) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'RO',
            ];
        }

        if ($hours = Setting::get('company_hours')) {
            $schema['openingHours'] = $hours;
        }

        $socials = array_values(array_filter([
            Setting::get('social_facebook'),
            Setting::get('social_instagram'),
            Setting::get('social_linkedin'),
        ]));

        if ($socials !== []) {
            $schema['sameAs'] = $socials;
        }

        return $schema;
    }
}
