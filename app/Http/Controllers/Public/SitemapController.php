<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Response;

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
            route('public.blog.index'),
        ];

        $postUrls = Post::published()->pluck('slug')->map(
            fn (string $slug) => route('public.blog.show', $slug)
        );

        $urls = collect($staticUrls)->merge($postUrls);

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
