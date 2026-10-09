<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\PageView;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_featured_and_remaining_posts(): void
    {
        $newest = Post::factory()->create(['title' => 'Cel mai nou', 'published_at' => now()]);
        $older = Post::factory()->create(['title' => 'Mai vechi', 'published_at' => now()->subDay()]);

        $this->get(route('public.blog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Blog/Index')
                ->where('featured.id', $newest->id)
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $older->id));
    }

    public function test_search_filters_by_title_and_content(): void
    {
        Post::factory()->create(['title' => 'Montaj parchet', 'body' => 'Text', 'published_at' => now()->subDay()]);
        Post::factory()->create(['title' => 'Instalatie electrica', 'body' => 'Despre parchet laminat', 'published_at' => now()->subDay()]);
        Post::factory()->create(['title' => 'Zugraveli', 'body' => 'Vopsea', 'published_at' => now()->subDay()]);

        $this->get(route('public.blog.index', ['q' => 'parchet']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 2)
                ->where('featured', null));
    }

    public function test_sorting_by_title_is_alphabetical(): void
    {
        Post::factory()->create(['title' => 'B eta', 'published_at' => now()->subDay()]);
        Post::factory()->create(['title' => 'A alfa', 'published_at' => now()]);
        Post::factory()->create(['title' => 'C gama', 'published_at' => now()->subDays(2)]);

        $this->get(route('public.blog.index', ['sort' => 'title']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('posts.data.0.title', 'A alfa')
                ->where('posts.data.1.title', 'B eta')
                ->where('posts.data.2.title', 'C gama'));
    }

    public function test_category_filter_only_returns_posts_in_category(): void
    {
        $category = Category::create(['name' => 'Instalatii', 'slug' => 'instalatii']);
        $inCategory = Post::factory()->create(['published_at' => now()->subDay()]);
        $inCategory->categories()->attach($category);
        Post::factory()->create(['published_at' => now()->subDay()]);

        $this->get(route('public.blog.index', ['category' => 'instalatii']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $inCategory->id));
    }

    public function test_popular_sort_orders_by_views(): void
    {
        $popular = Post::factory()->create(['title' => 'Popular', 'published_at' => now()->subDay()]);
        Post::factory()->create(['title' => 'Nepopular', 'published_at' => now()]);

        foreach (range(1, 5) as $ignored) {
            PageView::create([
                'url' => 'https://example.test/blog/'.$popular->slug,
                'path' => '/blog/'.$popular->slug,
                'device_type' => 'desktop',
                'method' => 'GET',
                'is_bot' => false,
                'created_at' => now(),
            ]);
        }

        $this->get(route('public.blog.index', ['sort' => 'popular']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('posts.data.0.id', $popular->id));
    }

    public function test_search_page_is_not_indexable(): void
    {
        Post::factory()->create(['published_at' => now()->subDay()]);

        $this->get(route('public.blog.index', ['q' => 'parchet']))
            ->assertOk()
            ->assertSee('noindex', false);
    }
}
