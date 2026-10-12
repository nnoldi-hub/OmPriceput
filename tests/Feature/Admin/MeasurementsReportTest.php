<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementsReportTest extends TestCase
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

    public function test_report_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('vanzari');

        $this->actingAs($user)
            ->get(route('admin.reports.measurements'))
            ->assertForbidden();
    }

    public function test_report_aggregates_measurements_in_range(): void
    {
        $client = Client::factory()->create(['name' => 'Maria Ionescu']);

        $living = Measurement::factory()->forClient($client)->create(['name' => 'Casa Unirii']);
        MeasurementObject::factory()->create(['measurement_id' => $living->id, 'name' => 'Living', 'length' => 4.5, 'width' => 3.2]);
        MeasurementObject::factory()->create(['measurement_id' => $living->id, 'name' => 'Dormitor', 'length' => 3, 'width' => 3]);

        $withOffer = Measurement::factory()->forClient($client)->completed()->create(['name' => 'Apartament Sud']);
        MeasurementObject::factory()->create(['measurement_id' => $withOffer->id, 'name' => 'Bucatarie', 'length' => 2, 'width' => 1]);
        $offer = Offer::factory()->create(['client_id' => $client->id, 'measurement_id' => $withOffer->id, 'total_amount' => 500]);

        Measurement::factory()->create(['name' => 'Inainte de perioada'])
            ->forceFill(['created_at' => now()->subMonths(7)])
            ->save();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.measurements'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Measurements')
                ->where('summary.measurements', 2)
                ->where('summary.completed', 1)
                ->where('summary.offers', 1)
                ->where('summary.totalArea', 25.4)
                ->where('summary.totalPerimeter', 33.4)
                ->where('summary.estimatedValue', 969)
                ->where('summary.offerValue', 500)
                ->where('byStatus.in_progress', 1)
                ->where('byStatus.completed', 1)
                ->has('measurements', 2)
                ->where('measurements.0.id', $withOffer->id)
                ->where('measurements.0.offer.id', $offer->id)
            );
    }

    public function test_report_skips_out_of_range_measurements(): void
    {
        $measurement = Measurement::factory()->create();
        $measurement->forceFill(['created_at' => now()->subMonths(3)])->save();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.measurements', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('summary.measurements', 0));
    }
}
