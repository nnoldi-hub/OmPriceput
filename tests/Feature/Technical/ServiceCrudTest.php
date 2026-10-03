<?php

namespace Tests\Feature\Technical;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $techUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->techUser = User::factory()->create();
        $this->techUser->assignRole('tehnic');
    }

    public function test_technician_can_create_and_update_a_service(): void
    {
        $this->actingAs($this->techUser)
            ->post(route('technical.services.store'), [
                'name' => 'Montaj mobila',
                'category' => 'montaj',
                'unit' => 'ora',
                'cost_price' => 80,
                'sale_price' => 150,
                'description' => 'Montaj si reglare mobilier.',
                'is_active' => true,
            ])
            ->assertRedirect(route('technical.services.index'));

        $service = Service::firstWhere('name', 'Montaj mobila');
        $this->assertNotNull($service);

        $this->actingAs($this->techUser)
            ->put(route('technical.services.update', $service), [
                'name' => 'Montaj mobila complexa',
                'category' => 'montaj',
                'unit' => 'ora',
                'cost_price' => 90,
                'sale_price' => 175,
                'description' => 'Actualizat.',
                'is_active' => true,
            ])
            ->assertRedirect(route('technical.services.index'));

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Montaj mobila complexa',
            'sale_price' => 175,
        ]);
    }
}
