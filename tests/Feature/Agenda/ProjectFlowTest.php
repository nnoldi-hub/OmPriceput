<?php

namespace Tests\Feature\Agenda;

use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Offer;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFlowTest extends TestCase
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

    public function test_measurement_can_be_started_from_a_project(): void
    {
        $client = Client::factory()->create(['name' => 'Maria Ionescu']);
        $project = Project::factory()->forClient($client)->create(['name' => 'Casa Popescu']);

        $this->actingAs($this->admin)
            ->post(route('agenda.projects.measurements.store', $project), ['name' => 'Masuratoare Casa Popescu'])
            ->assertRedirect(route('measurements.show', Measurement::first()));

        $this->assertDatabaseHas('measurements', [
            'name' => 'Masuratoare Casa Popescu',
            'project_id' => $project->id,
            'client_id' => $client->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_measurement_started_from_project_requires_name(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('agenda.projects.measurements.store', $project), [])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('measurements', 0);
    }

    public function test_project_page_exposes_measurements_and_offers(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->forClient($client)->create();

        $measurement = Measurement::factory()->forClient($client)->forProject($project)->create(['name' => 'M1']);
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 4, 'width' => 3]);

        Offer::factory()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'measurement_id' => $measurement->id,
            'title' => 'Oferta X',
            'total_amount' => 300,
        ]);

        $this->actingAs($this->admin)
            ->get(route('agenda.projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Agenda/Projects/Show')
                ->has('measurements', 1)
                ->where('measurements.0.name', 'M1')
                ->where('measurements.0.total_area', 12)
                ->has('offers', 1)
                ->where('offers.0.title', 'Oferta X')
                ->where('offers.0.total_amount', 300)
            );
    }

    public function test_offer_created_from_measurement_is_linked_to_project(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->forClient($client)->create();
        $measurement = Measurement::factory()->forClient($client)->forProject($project)->create();
        MeasurementObject::factory()->create(['measurement_id' => $measurement->id, 'length' => 3, 'width' => 2]);

        $this->actingAs($this->admin)
            ->post(route('measurements.offer', $measurement))
            ->assertRedirect(route('sales.offers.show', Offer::first()));

        $offer = Offer::first();
        $this->assertSame($project->id, $offer->project_id);
        $this->assertSame($measurement->id, $offer->measurement_id);
    }
}
