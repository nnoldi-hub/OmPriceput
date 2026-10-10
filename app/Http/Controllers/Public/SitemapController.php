<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect($this->staticUrls())
            ->map(fn (string $url) => ['loc' => $url])
            ->merge(Post::published()->latest('updated_at')->get()->map(fn (Post $post) => [
                'loc' => route('public.blog.show', $post->slug),
                'lastmod' => ($post->updated_at ?? $post->published_at)->toDateString(),
            ]))
            ->merge($this->categoryUrls())
            ->merge($this->tagUrls())
            ->merge($this->shopUrls());

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600');
    }

    /** @return array<int, string> */
    private function staticUrls(): array
    {
        return [
            route('public.home'),
            route('public.about'),
            route('public.services'),
            route('public.quote'),
            route('public.contact'),
            route('public.terms'),
            route('public.privacy'),
            route('public.blog.index'),
        ];
    }

    private function categoryUrls(): Collection
    {
        return Category::query()
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'loc' => route('public.blog.category', $category->slug),
                'lastmod' => ($category->updated_at ?? $category->created_at)->toDateString(),
            ]);
    }

    private function tagUrls(): Collection
    {
        return Tag::query()
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'loc' => route('public.blog.tag', $tag->slug),
                'lastmod' => ($tag->updated_at ?? $tag->created_at)->toDateString(),
            ]);
    }

    private function shopUrls(): Collection
    {
        if (Setting::get('shop_enabled', '0') !== '1') {
            return collect();
        }

        return collect([['loc' => route('public.shop.index')]])
            ->merge(Equipment::visibleInShop()->orderBy('name')->get()->map(fn (Equipment $equipment) => [
                'loc' => route('public.shop.show', $equipment->slug),
                'lastmod' => ($equipment->updated_at ?? $equipment->created_at)->toDateString(),
            ]));
    }
}
