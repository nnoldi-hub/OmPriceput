<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Seo;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        app(Seo::class)
            ->title('Blog')
            ->description('Ghiduri si sfaturi pentru intretinerea locuintei si a instalatiilor din casa sau apartament.')
            ->allowIndex();

        return Inertia::render('Public/Blog/Index', [
            'posts' => $this->publishedPosts()->paginate(9)->withQueryString(),
            'heading' => 'Blog',
            'description' => 'Ghiduri si sfaturi pentru intretinerea locuintei si a instalatiilor din casa sau apartament.',
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::published()
            ->with(['categories:id,name,slug', 'tags:id,name,slug', 'author:id,name'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->setAttribute('body_html', $this->renderBody($post->body));
        $post->setAttribute('gallery_images', $this->galleryImages($post));

        $this->applySeo($post);

        return Inertia::render('Public/Blog/Show', [
            'post' => $post,
            'related' => $this->relatedPosts($post),
        ]);
    }

    public function category(string $slug): Response
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        app(Seo::class)
            ->title('Categoria: '.$category->name)
            ->description($category->description ?: 'Articole din categoria '.$category->name.'.')
            ->allowIndex();

        return Inertia::render('Public/Blog/Index', [
            'posts' => $this->publishedPosts()
                ->whereHas('categories', fn ($query) => $query->where('categories.id', $category->id))
                ->paginate(9)
                ->withQueryString(),
            'heading' => $category->name,
            'description' => $category->description ?: 'Articole din categoria '.$category->name.'.',
        ]);
    }

    public function tag(string $slug): Response
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        app(Seo::class)
            ->title('Eticheta: '.$tag->name)
            ->description('Articole etichetate cu '.$tag->name.'.')
            ->allowIndex();

        return Inertia::render('Public/Blog/Index', [
            'posts' => $this->publishedPosts()
                ->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))
                ->paginate(9)
                ->withQueryString(),
            'heading' => '#'.$tag->name,
            'description' => 'Articole etichetate cu '.$tag->name.'.',
        ]);
    }

    private function publishedPosts()
    {
        return Post::published()->with(['categories:id,name,slug'])->latest('published_at');
    }

    /** @return Collection<int, Post> */
    private function relatedPosts(Post $post)
    {
        $categoryIds = $post->categories->pluck('id');

        $columns = ['id', 'title', 'slug', 'excerpt', 'published_at', 'cover_image'];

        $related = Post::published()
            ->whereKeyNot($post->id)
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereHas(
                'categories',
                fn ($categories) => $categories->whereIn('categories.id', $categoryIds)
            ))
            ->latest('published_at')
            ->take(3)
            ->get($columns);

        if ($related->count() < 3) {
            $extra = Post::published()
                ->whereKeyNot($post->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->latest('published_at')
                ->take(3 - $related->count())
                ->get($columns);

            $related = $related->concat($extra);
        }

        return $related->values();
    }

    private function applySeo(Post $post): void
    {
        $description = $post->meta_description ?: $post->excerpt;
        $title = $post->meta_title ?: $post->title;

        $schemas = [[
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $description,
            'image' => array_values(array_filter([$post->cover_image_url])),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $post->canonical_url ?: route('public.blog.show', $post->slug),
            ],
            'author' => $post->author
                ? ['@type' => 'Person', 'name' => $post->author->name]
                : ['@type' => 'Organization', 'name' => config('app.name')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('branding/logo-trim.png')],
            ],
        ]];

        if ($faq = $this->faqSchema($post)) {
            $schemas[] = $faq;
        }

        app(Seo::class)
            ->title($title)
            ->description($description)
            ->image($post->og_image_url ?: $post->cover_image_url)
            ->allowIndex()
            ->canonical($post->canonical_url)
            ->robots($post->meta_robots)
            ->type('article')
            ->ogTitle($post->og_title)
            ->ogDescription($post->og_description)
            ->ogImage($post->og_image_url)
            ->twitterTitle($post->twitter_title)
            ->twitterDescription($post->twitter_description)
            ->twitterImage($post->twitter_image_url)
            ->jsonLd($schemas);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function faqSchema(Post $post): ?array
    {
        $items = collect($post->faq ?? [])
            ->filter(fn ($item) => ! empty($item['question']) && ! empty($item['answer']))
            ->values();

        if ($items->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ])->all(),
        ];
    }

    /** @return array<int, array{id: int, url: string, alt: ?string, title: ?string}> */
    private function galleryImages(Post $post): array
    {
        $ids = collect($post->gallery ?? [])->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return [];
        }

        $media = Media::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $media->get($id))
            ->filter()
            ->map(fn (Media $item) => [
                'id' => $item->id,
                'url' => $item->url,
                'alt' => $item->alt,
                'title' => $item->title,
            ])
            ->values()
            ->all();
    }

    private function renderBody(?string $body): string
    {
        $body = (string) $body;

        if ($body === '') {
            return '';
        }

        if (preg_match('/<[a-z][\s\S]*>/i', $body)) {
            return $body;
        }

        return nl2br(e($body));
    }
}
