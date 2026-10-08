<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $staticUrls = [
            route('public.home'),
            route('public.about'),
            route('public.services'),
            route('public.quote'),
            route('public.contact'),
            route('public.terms'),
            route('public.privacy'),
            route('public.blog.index'),
        ];

        $urls = collect($staticUrls)
            ->map(fn (string $url) => ['loc' => $url, 'lastmod' => now()->toDateString()])
            ->merge(Post::published()->latest('updated_at')->get()->map(fn (Post $post) => [
                'loc' => route('public.blog.show', $post->slug),
                'lastmod' => ($post->updated_at ?? $post->published_at)->toDateString(),
            ]))
            ->merge($this->shopUrls());

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    private function shopUrls(): Collection
    {
        if (Setting::get('shop_enabled', '0') !== '1') {
            return collect();
        }

        return collect([['loc' => route('public.shop.index'), 'lastmod' => now()->toDateString()]])
            ->merge(Equipment::visibleInShop()->orderBy('name')->get()->map(fn (Equipment $equipment) => [
                'loc' => route('public.shop.show', $equipment->slug),
                'lastmod' => ($equipment->updated_at ?? $equipment->created_at)->toDateString(),
            ]));
    }
}
