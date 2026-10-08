<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Blog/Index', [
            'posts' => Post::published()->latest('published_at')->paginate(9)->withQueryString(),
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(3)
            ->get(['title', 'slug', 'excerpt', 'published_at']);

        $this->applySeo($post);

        return Inertia::render('Public/Blog/Show', [
            'post' => $post,
            'related' => $related,
        ]);
    }

    private function applySeo(Post $post): void
    {
        $description = $post->meta_description ?: $post->excerpt;

        app(Seo::class)
            ->title($post->meta_title ?: $post->title)
            ->description($description)
            ->allowIndex()
            ->jsonLd([[
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'description' => $description,
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => route('public.blog.show', $post->slug),
                ],
                'author' => ['@type' => 'Organization', 'name' => config('app.name')],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config('app.name'),
                    'logo' => ['@type' => 'ImageObject', 'url' => asset('branding/logo-trim.png')],
                ],
            ]]);
    }
}
