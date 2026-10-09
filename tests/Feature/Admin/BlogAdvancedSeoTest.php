<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogAdvancedSeoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_public_post_renders_faq_schema(): void
    {
        $post = Post::factory()->create([
            'published_at' => now()->subDay(),
            'faq' => [['question' => 'Cum functioneaza?', 'answer' => 'Foarte simplu.']],
        ]);

        $this->get(route('public.blog.show', $post->slug))
            ->assertOk()
            ->assertSee('FAQPage', false)
            ->assertSee('Cum functioneaza?', false);
    }

    public function test_auto_internal_links_are_applied_on_save(): void
    {
        $target = Post::factory()->create(['title' => 'Cum alegi un flex profesional', 'slug' => 'cum-alegi-un-flex-profesional']);

        $response = $this->actingAs($this->admin)->post(route('admin.blog.store'), [
            'title' => 'Articol nou despre instalatii',
            'slug' => 'articol-nou',
            'body' => 'Text introductiv despre cum alegi un flex profesional in casa.',
            'status' => 'draft',
            'auto_internal_links' => 1,
        ]);

        $response->assertRedirect(route('admin.blog.index'));

        $post = Post::where('slug', 'articol-nou')->firstOrFail();

        $this->assertStringContainsString('<a href="'.route('public.blog.show', $target->slug).'"', $post->body);
    }

    public function test_faq_rows_without_answer_are_discarded(): void
    {
        $this->actingAs($this->admin)->post(route('admin.blog.store'), [
            'title' => 'Articol cu FAQ',
            'slug' => 'articol-cu-faq',
            'body' => 'Continut.',
            'status' => 'draft',
            'faq' => [
                ['question' => 'Intrebare buna?', 'answer' => 'Raspuns clar.'],
                ['question' => 'Fara raspuns?', 'answer' => ''],
            ],
        ]);

        $post = Post::where('slug', 'articol-cu-faq')->firstOrFail();

        $this->assertCount(1, $post->faq);
        $this->assertSame('Intrebare buna?', $post->faq[0]['question']);
    }
}
