<?php

namespace Tests\Feature\Measurement;

use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementTest extends TestCase
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

    public function test_non_admin_cannot_access_measurements(): void
    {
        $sales = User::factory()->create();
        $sales->assignRole('vanzari');

        $this->actingAs($sales)->get(route('measurements.index'))->assertForbidden();
    }

    public function test_measurements_index_lists_measurements_with_totals(): void
    {
        $client = Client::factory()->create(['name' => 'Maria Ionescu']);
        $project = Project::factory()->create(['name' => 'Montaj casa']);
        $measurement = Measurement::factory()->forClient($client)->forProject($project)->create(['name' => 'Casa Unirii']);

        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'name' => 'Living',
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 1,
        ]);
        MeasurementObject::factory()->create([
            'measurement_id' => $measurement->id,
            'name' => 'Garaj',
            'length' => 6,
            'width' => 3,
            'quantity' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('measurements.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Measurements/Index')
                ->has('measurements', 1)
                ->where('measurements.0.name', 'Casa Unirii')
                ->where('measurements.0.client_name', 'Maria Ionescu')
                ->where('measurements.0.project_name', 'Montaj casa')
                ->where('measurements.0.total_area', 32.4)
                ->where('measurements.0.total_perimeter', 33.4)
                ->has('measurements.0.objects', 2)
            );
    }

    public function test_measurement_can_be_created_with_optional_links(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($this->admin)->post(route('measurements.store'), [
            'name' => 'Apartament Morarilor',
            'location' => 'Str. Morarilor 12',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'in_progress',
        ])->assertRedirect();

        $this->assertDatabaseHas('measurements', [
            'name' => 'Apartament Morarilor',
            'location' => 'Str. Morarilor 12',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_measurement_creation_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('measurements.store'), ['status' => 'in_progress'])
            ->assertSessionHasErrors('name');
    }

    public function test_marking_measurement_completed_sets_measured_at(): void
    {
        $measurement = Measurement::factory()->create(['status' => 'in_progress', 'measured_at' => null]);

        $this->actingAs($this->admin)
            ->patch(route('measurements.update', $measurement), ['status' => 'completed'])
            ->assertRedirect();

        $this->assertDatabaseHas('measurements', [
            'id' => $measurement->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($measurement->fresh()->measured_at);
    }

    public function test_object_can_be_added_updated_and_removed(): void
    {
        $measurement = Measurement::factory()->create();

        $this->actingAs($this->admin)->post(route('measurements.objects.store', $measurement), [
            'name' => 'Living',
        ])->assertRedirect();

        $object = MeasurementObject::first();
        $this->assertSame('Living', $object->name);

        $this->actingAs($this->admin)->patch(route('measurements.objects.update', $object), [
            'name' => 'Living mare',
            'length' => 4.5,
            'width' => 3.2,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('measurement_objects', [
            'id' => $object->id,
            'name' => 'Living mare',
            'length' => 4.5,
            'width' => 3.2,
        ]);

        $this->actingAs($this->admin)->delete(route('measurements.objects.destroy', $object))->assertRedirect();
        $this->assertDatabaseMissing('measurement_objects', ['id' => $object->id]);
    }

    public function test_object_creation_requires_name(): void
    {
        $measurement = Measurement::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('measurements.objects.store', $measurement), ['length' => 4])
            ->assertSessionHasErrors('name');
    }

    public function test_deleting_measurement_cascades_objects(): void
    {
        $measurement = Measurement::factory()->create();
        MeasurementObject::factory()->count(3)->create(['measurement_id' => $measurement->id]);

        $this->actingAs($this->admin)->delete(route('measurements.destroy', $measurement))->assertRedirect();

        $this->assertDatabaseMissing('measurements', ['id' => $measurement->id]);
        $this->assertDatabaseCount('measurement_objects', 0);
    }
}
