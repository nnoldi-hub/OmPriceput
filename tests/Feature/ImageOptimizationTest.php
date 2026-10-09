<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_upload_is_converted_to_webp_and_resized(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Storage::fake('public');

        $file = UploadedFile::fake()->image('mare.jpg', 2000, 1000);

        $this->actingAs($admin)
            ->post(route('admin.media.store'), ['files' => [$file]])
            ->assertRedirect();

        $media = Media::firstOrFail();

        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertSame(1600, $media->width);
        $this->assertSame(800, $media->height);
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
    }

    public function test_command_converts_existing_images_and_updates_references(): void
    {
        Storage::fake('public');

        $image = imagecreatetruecolor(400, 300);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 120, 200));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put('media/abc.png', $bytes);

        $media = Media::create(['disk' => 'public', 'path' => 'media/abc.png', 'original_name' => 'abc.png']);

        DB::table('posts')->insert([
            'title' => 'Test',
            'slug' => 'test-optimizare',
            'body' => '<p><img src="/storage/media/abc.png"></p>',
            'cover_image' => 'media/abc.png',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('images:optimize', ['--min-bytes' => 0])->assertExitCode(0);

        $this->assertTrue(Storage::disk('public')->exists('media/abc.webp'));
        $this->assertFalse(Storage::disk('public')->exists('media/abc.png'));

        $this->assertSame('media/abc.webp', $media->fresh()->path);
        $this->assertSame('media/abc.webp', DB::table('posts')->value('cover_image'));

        $body = (string) DB::table('posts')->value('body');
        $this->assertStringContainsString('media/abc.webp', $body);
        $this->assertStringNotContainsString('media/abc.png', $body);
    }
}
