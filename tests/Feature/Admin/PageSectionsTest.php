<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSectionsTest extends TestCase
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

    public function test_edit_page_returns_sections(): void
    {
        $page = Page::where('slug', 'acasa')->firstOrFail();
        $page->sections()->create([
            'section_key' => 'gallery',
            'content' => ['images' => ['/storage/media/lucrare.jpg']],
            'sort_order' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pages.edit', $page->id))
            ->assertOk()
            ->assertInertia(fn ($pageAssert) => $pageAssert
                ->component('Admin/Pages/Edit')
                ->has('page.sections', 1)
                ->where('page.sections.0.section_key', 'gallery')
                ->where('page.sections.0.content.images.0', '/storage/media/lucrare.jpg')
            );
    }

    public function test_update_page_persists_gallery_section(): void
    {
        $page = Page::where('slug', 'acasa')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.pages.update', $page->id), [
                'title' => 'Acasa',
                'status' => 'published',
                'sections' => [
                    ['section_key' => 'gallery', 'content' => ['images' => ['/storage/media/a.jpg', '/storage/media/b.jpg']], 'sort_order' => 0],
                ],
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('page_sections', [
            'page_id' => $page->id,
            'section_key' => 'gallery',
        ]);

        $saved = $page->sections()->where('section_key', 'gallery')->firstOrFail();

        $this->assertSame(['/storage/media/a.jpg', '/storage/media/b.jpg'], $saved->content['images']);
        $this->assertSame('gallery', $saved->content['type']);
    }
}