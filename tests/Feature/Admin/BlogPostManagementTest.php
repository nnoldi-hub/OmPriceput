<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPostManagementTest extends TestCase
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

    public function test_edit_page_returns_the_bound_post(): void
    {
        $post = Post::factory()->create(['title' => 'Articol existent', 'body' => 'Continut original.']);

        $this->actingAs($this->admin)
            ->get(route('admin.blog.edit', $post->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Blog/Edit')
                ->where('post.id', $post->id)
                ->where('post.title', 'Articol existent')
                ->where('post.body', 'Continut original.'));
    }

    public function test_update_modifies_the_existing_post_without_creating_a_new_one(): void
    {
        $post = Post::factory()->create(['title' => 'Vechi', 'body' => 'Vechi continut.']);

        $this->actingAs($this->admin)
            ->put(route('admin.blog.update', $post->id), [
                'title' => 'Titlu actualizat',
                'slug' => $post->slug,
                'body' => 'Continut nou.',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.blog.index'));

        $post->refresh();

        $this->assertSame('Titlu actualizat', $post->title);
        $this->assertSame('Continut nou.', $post->body);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_destroy_deletes_the_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.blog.destroy', $post->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
