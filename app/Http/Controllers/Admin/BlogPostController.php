<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\InternalLinker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Blog/Index', [
            'posts' => Post::with(['categories:id,name', 'author:id,name'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Edit', [
            'post' => null,
            'galleryMedia' => [],
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $post = Post::create($data);

        $post->categories()->sync($this->categoryIds($request));
        $post->tags()->sync($this->tagIds($request));

        return redirect()->route('admin.blog.index')->with('success', 'Articolul a fost creat.');
    }

    public function edit(Post $post): Response
    {
        $post->load(['categories:id,name', 'tags:id,name']);

        return Inertia::render('Admin/Blog/Edit', [
            'post' => $post,
            'galleryMedia' => $this->galleryMedia($post),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->validateData($request, $post));

        $post->categories()->sync($this->categoryIds($request));
        $post->tags()->sync($this->tagIds($request));

        return redirect()->route('admin.blog.index')->with('success', 'Articolul a fost actualizat.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'Articolul a fost sters.');
    }

    /**
     * @return array{categories: Collection, tags: Collection, authors: Collection}
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name', 'parent_id']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'authors' => User::orderBy('name')->get(['id', 'name']),
        ];
    }

    /** @return array<int, int> */
    private function categoryIds(Request $request): array
    {
        return collect($request->input('categories', []))->map(fn ($id) => (int) $id)->all();
    }

    /** @return array<int, int> */
    private function tagIds(Request $request): array
    {
        return collect($request->input('tags', []))->map(fn ($id) => (int) $id)->all();
    }

    private function validateData(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:posts,slug,'.($post?->id ?? 'NULL')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'cover_image_alt' => ['nullable', 'string', 'max:255'],
            'cover_image_caption' => ['nullable', 'string', 'max:255'],
            'cover_image_credit' => ['nullable', 'string', 'max:255'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'meta_robots' => ['nullable', 'in:index,follow,index,nofollow,noindex,follow,noindex,nofollow'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'max:4096'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['integer', 'exists:media,id'],
            'faq' => ['nullable', 'array'],
            'faq.*.question' => ['nullable', 'string', 'max:500'],
            'faq.*.answer' => ['nullable', 'string', 'max:2000'],
            'auto_internal_links' => ['nullable', 'boolean'],
        ]);

        unset($data['categories'], $data['tags']);

        $data['gallery'] = collect($data['gallery'] ?? [])->map(fn ($id) => (int) $id)->values()->all();

        $data['faq'] = collect($data['faq'] ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => trim((string) ($item['answer'] ?? '')),
            ])
            ->filter(fn ($item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();

        $data['auto_internal_links'] = $request->boolean('auto_internal_links');

        foreach (['slug', 'excerpt', 'meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'meta_robots', 'og_title', 'og_description', 'twitter_title', 'twitter_description', 'cover_image_alt', 'cover_image_caption', 'cover_image_credit', 'published_at'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title'], $post?->id);

        $data['author_id'] = ! empty($data['author_id'])
            ? (int) $data['author_id']
            : ($post?->author_id ?? $request->user()->id);

        foreach (['cover_image', 'og_image', 'twitter_image'] as $image) {
            if ($request->hasFile($image)) {
                $data[$image] = $request->file($image)->store('blog', 'public');
            } else {
                unset($data[$image]);
            }
        }

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if ($data['auto_internal_links']) {
            $data['body'] = app(InternalLinker::class)->link($data['body'], $post);
        }

        return $data;
    }

    /** @return Collection<int, Media> */
    private function galleryMedia(Post $post): Collection
    {
        $ids = collect($post->gallery ?? [])->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return collect();
        }

        $media = Media::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $media->get($id))->filter()->values();
    }

    private function uniqueSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title) ?: 'articol';
        $candidate = $base;
        $suffix = 2;

        while (Post::where('slug', $candidate)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
