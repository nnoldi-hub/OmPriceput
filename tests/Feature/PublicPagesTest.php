<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get(route('public.home'))->assertOk();
    }

    public function test_about_page_loads(): void
    {
        $this->get(route('public.about'))->assertOk();
    }

    public function test_services_page_loads(): void
    {
        $this->get(route('public.services'))->assertOk();
    }

    public function test_quote_request_page_loads(): void
    {
        Equipment::factory()->create(['category' => 'consumabil']);
        Equipment::factory()->create(['category' => 'scula']);
        Equipment::factory()->create(['category' => ' piesa', 'sku' => 'Piesa-1']);

        $this->get(route('public.quote'))->assertOk();
    }

    public function test_quote_request_creates_client_and_scheduled_visit(): void
    {
        $response = $this->post(route('public.lead.store'), [
            'name' => 'Ana Popescu',
            'phone' => '0721123456',
            'address' => 'Str. Valea Rosie 12, Bucuresti',
            'job_type' => 'reparatie',
            'notes' => 'Chiuvita scurge sub chiuvita.',
            'privacy_consent' => '1',
        ]);

        $response->assertRedirect();

        $client = Client::where('phone', '0721123456')->firstOrFail();

        $this->assertDatabaseHas('installations', [
            'client_id' => $client->id,
            'type' => 'verificare',
            'requested_type' => 'reparatie',
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('installations', [
            'client_id' => $client->id,
            'scheduled_at' => null,
        ]);
    }

    public function test_contact_page_loads(): void
    {
        $this->get(route('public.contact'))->assertOk();
    }

    public function test_blog_index_loads(): void
    {
        Post::factory()->create(['published_at' => now()->subDay()]);

        $this->get(route('public.blog.index'))->assertOk();
    }

    public function test_blog_show_loads_for_published_post(): void
    {
        $post = Post::factory()->create(['published_at' => now()->subDay()]);

        $this->get(route('public.blog.show', $post->slug))->assertOk();
    }

    public function test_blog_show_returns_404_for_unpublished_post(): void
    {
        $post = Post::factory()->create(['published_at' => null]);

        $this->get(route('public.blog.show', $post->slug))->assertNotFound();
    }

    public function test_sitemap_and_robots_are_reachable(): void
    {
        $this->get(route('sitemap'))->assertOk();
        $this->get(route('robots'))->assertOk();
    }
}
