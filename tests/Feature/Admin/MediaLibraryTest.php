<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        Storage::fake('public');
    }

    public function test_admin_can_view_media_library(): void
    {
        Media::create(['disk' => 'public', 'path' => 'media/photo.jpg', 'original_name' => 'photo.jpg']);

        $response = $this->actingAs($this->admin)->get(route('admin.media.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Media/Index')
            ->has('media.data', 1)
        );
    }

    public function test_non_admin_cannot_view_media_library(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.media.index'))->assertForbidden();
    }

    public function test_admin_can_upload_media(): void
    {
        $file = UploadedFile::fake()->image('articol.jpg', 100, 80);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.media.store'), ['files' => [$file]]);

        $response->assertRedirect();

        $media = Media::firstOrFail();

        $this->assertSame($this->admin->id, $media->uploaded_by);
        $this->assertSame(100, $media->width);
        $this->assertSame(80, $media->height);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
    }

    public function test_editor_upload_returns_json_location(): void
    {
        $file = UploadedFile::fake()->image('inline.jpg');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.media.store'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonStructure(['location', 'media' => [['id', 'url']]]);
    }

    public function test_media_json_endpoint_lists_images(): void
    {
        Media::create(['disk' => 'public', 'path' => 'media/a.jpg', 'original_name' => 'a.jpg']);

        $response = $this->actingAs($this->admin)->getJson(route('admin.media.json'));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.original_name', 'a.jpg');
    }

    public function test_admin_can_update_and_delete_media(): void
    {
        Storage::disk('public')->put('media/b.jpg', 'contents');

        $media = Media::create(['disk' => 'public', 'path' => 'media/b.jpg', 'original_name' => 'b.jpg']);

        $this->actingAs($this->admin)
            ->put(route('admin.media.update', $media), ['alt' => 'Text alternativ', 'title' => 'Titlu'])
            ->assertRedirect();

        $media->refresh();
        $this->assertSame('Text alternativ', $media->alt);
        $this->assertSame('Titlu', $media->title);

        $this->actingAs($this->admin)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertFalse(Storage::disk('public')->exists('media/b.jpg'));
    }
}
