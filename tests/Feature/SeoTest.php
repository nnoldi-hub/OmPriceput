<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_unique_title_description_and_local_business_schema(): void
    {
        $response = $this->get(route('public.home'));

        $response->assertOk();
        $response->assertSee('<title inertia>Reparații, montaje și întreținere - Om Priceput</title>', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('index,follow', false);
        $response->assertSee('<link rel="canonical" href="'.route('public.home').'"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('HomeAndConstructionBusiness', false);
    }

    public function test_public_page_is_indexable_but_login_page_is_not(): void
    {
        $this->get(route('public.home'))->assertOk()->assertSee('index,follow', false);

        $this->get(route('login'))->assertOk()->assertSee('noindex,nofollow', false);
    }

    public function test_blog_post_has_article_schema_and_canonical_url(): void
    {
        $post = Post::factory()->create([
            'title' => 'Cum repari o scurgere',
            'meta_title' => 'Cum repari o scurgere',
            'excerpt' => 'Ghid scurt pentru o scurgere la chiuveta.',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('public.blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee('<title inertia>Cum repari o scurgere - Om Priceput</title>', false);
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('<link rel="canonical" href="'.route('public.blog.show', $post->slug).'"', false);
    }

    public function test_terms_and_privacy_pages_have_titles_and_are_indexable(): void
    {
        $this->get(route('public.terms'))
            ->assertOk()
            ->assertSee('<title inertia>Termeni și condiții - Om Priceput</title>', false)
            ->assertSee('index,follow', false);

        $this->get(route('public.privacy'))
            ->assertOk()
            ->assertSee('<title inertia>Politica de confidențialitate - Om Priceput</title>', false)
            ->assertSee('index,follow', false);
    }

    public function test_sitemap_lists_public_pages_with_lastmod(): void
    {
        $post = Post::factory()->create(['published_at' => now()->subDay()]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('public.home'), false);
        $response->assertSee(route('public.terms'), false);
        $response->assertSee(route('public.privacy'), false);
        $response->assertSee(route('public.blog.show', $post->slug), false);
        $response->assertSee('<lastmod>', false);
    }

    public function test_sitemap_lists_shop_products_only_when_shop_is_enabled(): void
    {
        Equipment::factory()->create(['is_visible_in_shop' => true, 'slug' => 'produs-ascuns']);

        $this->get(route('sitemap'))->assertOk()->assertDontSee('produs-ascuns', false);

        Setting::query()->delete();
        Setting::set('shop_enabled', '1');

        $this->get(route('sitemap'))->assertOk()->assertSee('produs-ascuns', false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_shop_product_page_has_product_schema_and_cart_is_noindex(): void
    {
        Setting::query()->delete();
        Setting::set('shop_enabled', '1');
        Equipment::factory()->create(['is_visible_in_shop' => true, 'slug' => 'produs-test']);

        $this->get(route('public.shop.show', 'produs-test'))
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('index,follow', false);

        $this->get(route('public.shop.cart'))
            ->assertOk()
            ->assertSee('noindex,nofollow', false);
    }
}
