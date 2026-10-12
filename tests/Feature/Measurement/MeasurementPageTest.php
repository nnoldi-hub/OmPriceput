<?php

namespace Tests\Feature\Measurement;

use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeasurementPageTest extends TestCase
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

    public function test_show_renders_measurement_with_objects_and_pricing(): void
    {
        $measurement = Measurement::factory()->create(['name' => 'Casa Unirii']);
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'name' => 'Living',
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('measurements.show', $measurement))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Measurements/Show')
                ->where('measurement.name', 'Casa Unirii')
                ->where('measurement.total_area', 14.4)
                ->where('measurement.total_perimeter', 15.4)
                ->has('measurement.objects', 1)
                ->where('pricing.area_m2', 25)
                ->where('pricing.perimeter_ml', 10)
                ->where('pricing.estimate', 514)
            );
    }

    public function test_photos_can_be_uploaded(): void
    {
        Storage::fake('public');
        $measurement = Measurement::factory()->create();
        $file = UploadedFile::fake()->image('camera.png');

        $this->actingAs($this->admin)
            ->post(route('measurements.photos.store', $measurement), ['photos' => [$file]])
            ->assertRedirect();

        $photos = $measurement->fresh()->photos;
        $this->assertCount(1, $photos);

        $relative = str_starts_with($photos[0], '/storage/')
            ? substr($photos[0], strlen('/storage/'))
            : Str::after($photos[0], Storage::disk('public')->url('/'));
        Storage::disk('public')->assertExists($relative);
    }

    public function test_photo_validation_rejects_non_images(): void
    {
        Storage::fake('public');
        $measurement = Measurement::factory()->create();
        $file = UploadedFile::fake()->create('nota.pdf', 100);

        $this->actingAs($this->admin)
            ->post(route('measurements.photos.store', $measurement), ['photos' => [$file]])
            ->assertSessionHasErrors('photos.0');

        $this->assertNull($measurement->fresh()->photos);
    }

    public function test_photo_can_be_deleted(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('measurements/demo.jpg', 'x');
        $url = Storage::disk('public')->url('measurements/demo.jpg');
        $measurement = Measurement::factory()->create(['photos' => [$url]]);

        $this->actingAs($this->admin)
            ->delete(route('measurements.photos.destroy', $measurement), ['photo' => $url])
            ->assertRedirect();

        $this->assertEmpty($measurement->fresh()->photos);
        Storage::disk('public')->assertMissing('measurements/demo.jpg');
    }

    public function test_measurement_details_can_be_updated(): void
    {
        $measurement = Measurement::factory()->create(['name' => 'Casa']);

        $this->actingAs($this->admin)->patch(route('measurements.update', $measurement), [
            'location' => 'Str. Unirii 5',
            'notes' => 'Acces prin spate.',
        ])->assertRedirect();

        $this->assertDatabaseHas('measurements', [
            'id' => $measurement->id,
            'location' => 'Str. Unirii 5',
            'notes' => 'Acces prin spate.',
        ]);
    }
}
