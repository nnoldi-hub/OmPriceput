<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    private const PER_PAGE = 9;

    private const DESCRIPTION = 'Articole și ghiduri despre reparații, instalații electrice și sanitare, zugrăveli și întreținerea locuinței.';

    /** @var array<string, string> */
    private const SORTS = [
        'newest' => 'Cele mai noi',
        'oldest' => 'Cele mai vechi',
        'popular' => 'Cele mai citite',
        'title' => 'Alfabetic (A–Z)',
    ];

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $seo = app(Seo::class)->title('Blog')->description(self::DESCRIPTION);
       $this->applyIndexability($seo, $request, $filters, $filters['category'] !== null || $filters['tag'] !== null);


        return $this->render($request, $filters, 'Blog', self::DESCRIPTION);
    }

    public function category(Request $request, string $slug): Response
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $filters = $this->filters($request);
        $filters['category'] = $category->slug;

        $description = $category->description ?: 'Articole din categoria '.$category->name.'.';

        $seo = app(Seo::class)->title('Categoria: '.$category->name)->description($description);
        $this->applyIndexability($seo, $request, $filters);

        return $this->render($request, $filters, $category->name, $description);
    }

    public function tag(Request $request, string $slug): Response
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $filters = $this->filters($request);
        $filters['tag'] = $tag->slug;

        $description = 'Articole etichetate cu '.$tag->name.'.';

        $seo = app(Seo::class)->title('Eticheta: '.$tag->name)->description($description);
        $this->applyIndexability($seo, $request, $filters, true);

        return $this->render($request, $filters, '#'.$tag->name, $description);
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

    /**
     * @param  array{q: string, sort: string, category: ?string, tag: ?string}  $filters
     */
    private function render(Request $request, array $filters, string $heading, string $description): Response
    {
        $featured = $this->featuredPost($request, $filters);

        $query = $this->filteredPosts($filters);

        if ($featured) {
            $query->whereKeyNot($featured->getKey());
        }

        $posts = $filters['sort'] === 'popular'
            ? $this->paginateByPopularity($query, $request)
            : $this->applySort($query, $filters['sort'])->paginate(self::PER_PAGE)->withQueryString();

        $posts->through(fn (Post $post): Post => $this->decorate($post));

        return Inertia::render('Public/Blog/Index', [
            'posts' => $posts,
            'featured' => $featured ? $this->decorate($featured) : null,
            'filters' => $filters,
            'sorts' => collect(self::SORTS)->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'categories' => $this->categoryOptions(),
            'tags' => $this->tagOptions(),
            'total' => $posts->total() + ($featured ? 1 : 0),
            'heading' => $heading,
            'description' => $description,
        ]);
    }

    /** Articolul evidențiat se afișează doar pe prima pagină, fără căutare sau filtre active. */
    private function featuredPost(Request $request, array $filters): ?Post
    {
        $isDefaultView = $filters['q'] === ''
            && $filters['category'] === null
            && $filters['tag'] === null
            && $filters['sort'] === 'newest'
            && $request->integer('page') <= 1;

        if (! $isDefaultView) {
            return null;
        }

        return Post::published()
            ->with(['categories:id,name,slug', 'author:id,name'])
            ->latest('published_at')
            ->first();
    }

    /**
     * @param  array{q: string, sort: string, category: ?string, tag: ?string}  $filters
     */
    private function filteredPosts(array $filters): Builder
    {
        return Post::published()
            ->with(['categories:id,name,slug', 'author:id,name'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['q'], '%_\\').'%';

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->when($filters['category'], fn (Builder $query, string $slug): Builder => $query
                ->whereHas('categories', fn ($categories) => $categories->where('slug', $slug)))
            ->when($filters['tag'], fn (Builder $query, string $slug): Builder => $query
                ->whereHas('tags', fn ($tags) => $tags->where('slug', $slug)));
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest('published_at'),
            'title' => $query->orderBy('title'),
            default => $query->latest('published_at'),
        };
    }

    private function paginateByPopularity(Builder $query, Request $request): LengthAwarePaginator
    {
        $posts = $query->get();
        $views = $this->viewCounts($posts->pluck('slug'));

        $sorted = $posts
            ->sort(function (Post $a, Post $b) use ($views): int {
                $comparison = ($views[$b->slug] ?? 0) <=> ($views[$a->slug] ?? 0);

                return $comparison !== 0
                    ? $comparison
                    : ($b->published_at?->getTimestamp() <=> $a->published_at?->getTimestamp());
            })
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $sorted->forPage($page, self::PER_PAGE)->values(),
            $sorted->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ],
        );
    }

    /** @param  Collection<int, string>  $slugs */
    private function viewCounts(Collection $slugs): Collection
    {
        if ($slugs->isEmpty()) {
            return collect();
        }

        return PageView::query()
            ->human()
            ->whereIn('path', $slugs->map(fn (string $slug): string => '/blog/'.$slug)->all())
            ->selectRaw('path, count(*) as views')
            ->groupBy('path')
            ->pluck('views', 'path')
            ->mapWithKeys(fn ($views, string $path): array => [
                Str::after($path, '/blog/') => (int) $views,
            ]);
    }

    /** @return Collection<int, Category> */
    private function categoryOptions(): Collection
    {
        return Category::query()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->filter(fn (Category $category): bool => $category->posts_count > 0)
            ->values();
    }

    /** @return Collection<int, Tag> */
    private function tagOptions(): Collection
    {
        return Tag::query()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->filter(fn (Tag $tag): bool => $tag->posts_count > 0)
            ->values();
    }

    private function decorate(Post $post): Post
    {
        return $post->setAttribute('reading_minutes', $this->readingMinutes($post->body));
    }

    private function readingMinutes(?string $body): int
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $body)));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text));

        return max(1, (int) ceil($words / 200));
    }

    /** @return array{q: string, sort: string, category: ?string, tag: ?string} */
    private function filters(Request $request): array
    {
        $sort = $request->string('sort')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'newest',
            'category' => $request->string('category')->toString() ?: null,
            'tag' => $request->string('tag')->toString() ?: null,
        ];
    }

    private function applyIndexability(Seo $seo, Request $request, array $filters, bool $forceNoindex = false): void
{
    if (
        $forceNoindex
        || $filters['q'] !== ''
        || $filters['sort'] !== 'newest'
        || $request->integer('page') > 1
    ) {
        $seo->noindex();

        return;
    }

    $seo->allowIndex();
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
