<?php

namespace Tests\Feature\Public;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_static_blog_and_taxonomy_urls(): void
    {
        $post = Post::factory()->create(['published_at' => now()->subDay()]);
        $category = Category::create(['name' => 'Instalatii', 'slug' => 'instalatii']);
        $tag = Tag::create(['name' => 'DIY', 'slug' => 'diy']);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('urlset', false)
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.blog.index'), false)
            ->assertSee(route('public.blog.category', $category->slug), false)
            ->assertSee(route('public.blog.tag', $tag->slug), false)
            ->assertSee(route('public.blog.show', $post->slug), false);
    }
}
